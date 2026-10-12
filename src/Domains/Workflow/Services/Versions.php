<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Records a product's versions: a snapshot of what it holds after each write,
 * the changes since the version before, and who made them.
 */
final class Versions
{
    /**
     * Set while a revert or transition runs, so the write it performs is
     * recorded under its action rather than as a plain update.
     *
     * @var array{action: string, comment: string|null}|null
     */
    private ?array $context = null;

    /**
     * Run a callback whose writes are recorded under the given action.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function as(string $action, ?string $comment, Closure $callback): mixed
    {
        $previous = $this->context;
        $this->context = ['action' => $action, 'comment' => $comment];

        try {
            return $callback();
        } finally {
            $this->context = $previous;
        }
    }

    /**
     * Record a version if anything changed since the last one. Returns the
     * version recorded, or the latest when nothing changed.
     */
    /**
     * @param  array<string, mixed>|null  $snapshot  The product's snapshot(), when the caller has just taken it.
     */
    public function record(ProductModel $product, string $action, ?string $comment = null, ?array $snapshot = null): VersionModel
    {
        $action = $this->context['action'] ?? $action;
        $comment ??= $this->context['comment'] ?? null;

        $latest = $this->latest($product);
        $snapshot ??= $this->snapshot($product);
        $changes = $this->diff($latest->snapshot ?? [], $snapshot);

        // A write that changed nothing is not history — unless it is a step
        // of the workflow, which is worth recording on its own.
        if ($latest !== null && $changes === [] && $action === 'updated') {
            return $latest;
        }

        $author = Auth::user();

        return VersionModel::query()->create([
            'versionable_type' => $product->getMorphClass(),
            'versionable_id' => $product->getKey(),
            'version' => ($latest->version ?? 0) + 1,
            'action' => $action,
            'snapshot' => $snapshot,
            'changes' => $changes,
            'comment' => $comment,
            'author_type' => $author instanceof Model ? $author->getMorphClass() : null,
            'author_id' => $author instanceof Model ? (string) $author->getKey() : null,
        ]);
    }

    public function latest(ProductModel $product): ?VersionModel
    {
        return VersionModel::query()
            ->where('versionable_type', $product->getMorphClass())
            ->where('versionable_id', $product->getKey())
            ->orderByDesc('version')
            ->first();
    }

    public function find(ProductModel $product, int $version): ?VersionModel
    {
        return VersionModel::query()
            ->where('versionable_type', $product->getMorphClass())
            ->where('versionable_id', $product->getKey())
            ->where('version', $version)
            ->first();
    }

    /**
     * The published snapshot of each product that has one, keyed by product
     * id, in one query however many products are asked for.
     *
     * @param  iterable<int, ProductModel>  $products
     * @return array<string, array<string, mixed>>
     */
    public function publishedSnapshots(iterable $products): array
    {
        $ids = [];

        foreach ($products as $product) {
            if ($product->published_version !== null) {
                $ids[] = $product->id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $versions = Tables::name('versions');
        $table = Tables::name('products');

        return VersionModel::query()
            ->join($table, function (JoinClause $join) use ($versions, $table): void {
                $join->on($table.'.id', '=', $versions.'.versionable_id')
                    ->on($table.'.published_version', '=', $versions.'.version');
            })
            ->where($versions.'.versionable_type', (new ProductModel)->getMorphClass())
            ->whereIn($table.'.id', $ids)
            ->get([$versions.'.versionable_id', $versions.'.snapshot'])
            ->mapWithKeys(fn (VersionModel $version): array => [(string) $version->versionable_id => $version->snapshot])
            ->all();
    }

    /**
     * Everything a product holds, in API shape: its own data, and the values
     * it inherits at this moment, so a published snapshot reads complete.
     *
     * @return array<string, mixed>
     */
    public function snapshot(ProductModel $product): array
    {
        $product->unsetRelation('parent')->unsetRelation('categories')->unsetRelation('associations');
        $product->load(['family', 'owner', 'parent.parent', 'categories', 'associations.type', 'associations.target']);

        $own = app(Associations::class)->present($product, inherit: false);

        return [
            'identifier' => $product->identifier,
            'family' => $product->family?->code,
            'parent' => $product->parent?->code,
            'owner' => $product->owner?->code,
            'enabled' => $product->enabled,
            'status' => $product->status->value,
            'values' => Values::toStandard($product->ownValues()),
            'inherited_values' => Values::toStandard($product->inheritedValues()),
            'categories' => $product->categories->map(fn (CategoryModel $category): string => $category->code)->values()->all(),
            'associations' => $own['associations'],
            'quantified_associations' => $own['quantified_associations'],
        ];
    }

    /**
     * Whether the product holds anything other than what its latest version
     * recorded, status aside.
     */
    /**
     * @param  array<string, mixed>|null  $snapshot  The product's snapshot(), when the caller has just taken it.
     */
    public function hasChanges(ProductModel $product, ?array $snapshot = null): bool
    {
        $latest = $this->latest($product);

        if ($latest === null) {
            return true;
        }

        $changes = $this->diff($latest->snapshot, $snapshot ?? $this->snapshot($product));
        unset($changes['status']);

        return $changes !== [];
    }

    /**
     * Remove the versions of deleted products.
     *
     * @param  array<int, string>  $ids
     */
    public function forget(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        VersionModel::query()
            ->where('versionable_type', (new ProductModel)->getMorphClass())
            ->whereIn('versionable_id', $ids)
            ->delete();
    }

    /**
     * Top-level fields that differ, and per-attribute changes in values.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function diff(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $value) {
            if ($key === 'values' || $key === 'inherited_values') {
                continue;
            }

            if (($before[$key] ?? null) !== $value) {
                $changes[$key] = ['old' => $before[$key] ?? null, 'new' => $value];
            }
        }

        /** @var array<string, mixed> $old */
        $old = is_array($before['values'] ?? null) ? $before['values'] : [];
        /** @var array<string, mixed> $new */
        $new = is_array($after['values'] ?? null) ? $after['values'] : [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $code) {
            if (($old[$code] ?? null) !== ($new[$code] ?? null)) {
                $changes['values.'.$code] = ['old' => $old[$code] ?? null, 'new' => $new[$code] ?? null];
            }
        }

        return $changes;
    }
}
