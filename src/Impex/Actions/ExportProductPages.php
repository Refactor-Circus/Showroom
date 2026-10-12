<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Impex\Domains\Flow\Support\ResumableAction;
use RefactorCircus\Impex\Domains\Run\Data\Resume;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetStorage;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Data\ValueFilter;
use RefactorCircus\Showroom\Domains\Product\Actions\ListProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\ListProductsData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine;
use RefactorCircus\Showroom\Domains\Search\Services\DatabaseEngine;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductPagesExportedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductPagesExportingActionEvent;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;
use RefactorCircus\Showroom\Impex\ExportRecord;

/**
 * Writes the products a search matches as JSONL part files, a page each.
 *
 * Object stores cannot append, and a Lambda invocation cannot outlive its
 * ceiling, so each page is its own file and the action yields between pages
 * when time runs short; JoinProductExport stitches the parts together.
 */
final class ExportProductPages extends ResumableAction
{
    /**
     * @param  array<string, mixed>  $query  ListProductsData input, plus `scope` and `locales`.
     * @return array{parts: int, count: int}|Resume
     */
    public function execute(array $query, string $runId, bool $published = false): array|Resume
    {
        ProductPagesExportingActionEvent::dispatch($query, $runId, $published);

        $result = $this->perform($query, $runId, $published);

        if ($result instanceof Resume) {
            // Yielding: the cursor names the next page to write.
            ['page' => $next, 'count' => $count] = self::progress($result->cursor);

            ProductPagesExportedActionEvent::dispatch($runId, $next - 1, $count, false);
        } else {
            ProductPagesExportedActionEvent::dispatch($runId, $result['parts'], $result['count'], true);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{parts: int, count: int}|Resume
     */
    private function perform(array $query, string $runId, bool $published): array|Resume
    {
        ['page' => $page, 'count' => $count, 'after' => $after] = self::progress($this->cursor());

        $storage = app(AssetStorage::class);
        $disk = Storage::disk($storage->diskName());
        $filter = ValueFilter::fromValidated($query);
        $perPage = (int) config('showroom.impex.export_page_size', 500);
        $engine = $this->engine();

        while (true) {
            // Validated when the export started.
            $filters = ListProductsData::fromValidated(
                ['sort' => 'identifier', 'page' => $page, 'per_page' => $perPage] + $query + ($published ? ['published' => true] : []),
            );

            if ($engine instanceof DatabaseEngine) {
                // Walked by key: no page rescans what came before, and
                // nothing is counted. A cursor from before keys were kept
                // names only its page, so that one page is found by offset.
                $keys = $engine->keyset($filters->query(), $after, $perPage + 1, $after === null ? ($page - 1) * $perPage : 0);
                $more = count($keys) > $perPage;
                $keys = array_slice($keys, 0, $perPage);
                $products = $this->load(array_column($keys, 1), $published);
                $after = $keys === [] ? $after : $keys[array_key_last($keys)];
            } else {
                $found = app(ListProductsAction::class)->execute($filters);
                $more = $found->count() >= $perPage && $found->hasMorePages();
                $products = $found->getCollection();

                if (! $published) {
                    $products->load(Associations::eagerLoads());
                }
            }

            $lines = [];
            $snapshots = $published ? app(Versions::class)->publishedSnapshots($products) : [];

            foreach ($products as $product) {
                $record = $published
                    ? ExportRecord::fromSnapshot($product, $snapshots[$product->id] ?? null, $filter)
                    : ExportRecord::of($product, $filter);

                if ($record !== null) {
                    $lines[] = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }

            if ($lines !== []) {
                $disk->put(self::part($runId, $page), implode("\n", $lines)."\n");
                $count += count($lines);
            }

            if (! $more) {
                return ['parts' => $page, 'count' => $count];
            }

            $page++;

            if ($this->shouldYield()) {
                return $this->yieldTo(json_encode(['page' => $page, 'count' => $count, 'after' => $after], JSON_THROW_ON_ERROR));
            }
        }
    }

    private function engine(): SearchEngine
    {
        return app(SearchEngine::class);
    }

    /**
     * Relations an exported working copy reads: owner, categories and
     * associations, the product's own and inherited.
     *
     * @return array<int, string>
     */
    public static function eagerLoads(): array
    {
        return [
            'family', 'owner', 'categories',
            'parent.owner', 'parent.categories',
            'parent.parent.owner', 'parent.parent.categories',
            ...Associations::eagerLoads(),
        ];
    }

    /**
     * Products by id, in the order given.
     *
     * @param  array<int, string>  $ids
     * @return Collection<int, ProductModel>
     */
    private function load(array $ids, bool $published): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        $products = ProductModel::query()
            // A published record comes from its snapshot alone.
            ->with($published ? [] : self::eagerLoads())
            ->whereKey($ids)
            ->get()
            ->keyBy('id');

        return new Collection(array_values(array_filter(array_map(
            fn (string $id): ?ProductModel => $products->get($id),
            $ids,
        ))));
    }

    /**
     * The page to write next, the records written so far, and the key of
     * the last product read, from a cursor.
     *
     * @return array{page: int, count: int, after: array{0: string, 1: string}|null}
     */
    private static function progress(?string $cursor): array
    {
        $state = json_decode((string) ($cursor ?? '{"page":1,"count":0}'), true);

        if (! is_array($state)) {
            return ['page' => 1, 'count' => 0, 'after' => null];
        }

        $after = $state['after'] ?? null;

        return [
            'page' => (int) ($state['page'] ?? 1),
            'count' => (int) ($state['count'] ?? 0),
            'after' => is_array($after) && is_string($after[0] ?? null) && is_string($after[1] ?? null) ? [$after[0], $after[1]] : null,
        ];
    }

    /**
     * Where one page of an export is written.
     */
    public static function part(string $runId, int $page): string
    {
        return trim((string) config('showroom.impex.export_path', 'showroom/exports'), '/').'/'.$runId.'/part-'.str_pad((string) $page, 6, '0', STR_PAD_LEFT).'.jsonl';
    }
}
