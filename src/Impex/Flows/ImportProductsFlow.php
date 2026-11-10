<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Flows;

use RefactorCircus\Impex\Domains\Flow\Support\Flow;
use RefactorCircus\Showroom\Impex\Actions\UpsertProductChunk;
use RefactorCircus\Showroom\Impex\ProductChunkSummary;
use RefactorCircus\Showroom\Impex\Sources\FileProductSource;

/**
 * `showroom:import-products` — a `.csv` or `.jsonl` asset into products.
 *
 * One batch step however many rows. Rows are written `showroom.impex.chunk`
 * at a time, each chunk by one call to UpsertProductsAction, so a failed
 * row is counted and kept rather than failing the run — unless more fail
 * than `showroom.impex.allow_failures` tolerates. With `$captureChanges`
 * false the writes are not recorded for product webhooks.
 *
 * The result counts rows: `total`, `succeeded`, `failed` and `unchanged`.
 * Each failed row's reason is kept on its chunk's batch item, by its row in
 * the file.
 */
final class ImportProductsFlow extends Flow
{
    /**
     * @return array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int}
     */
    public function handle(string $asset, string $format, string $mode = 'upsert', bool $captureChanges = true): array
    {
        $this->tag('showroom', 'import');

        $batch = $this->batch(FileProductSource::class, $asset, $format, $mode, $captureChanges, (int) config('showroom.impex.chunk', 500))
            ->using(UpsertProductChunk::class)
            // One chunk of rows per seeded page, as many rows as a page held before.
            ->chunk(1)
            // Rows, not chunks, are held to the tolerated share, below.
            ->allowFailures(1.0)
            ->tries((int) config('showroom.impex.tries', 1))
            ->run();

        /** @var array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int} $summary */
        $summary = $this->sideEffect('rows', fn (): array => app(ProductChunkSummary::class)->of((string) $batch['batch_id']));

        ProductChunkSummary::tolerate($summary, (float) config('showroom.impex.allow_failures', 1.0));

        return $summary;
    }
}
