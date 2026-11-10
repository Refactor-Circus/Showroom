<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Flows;

use RefactorCircus\Impex\Domains\Flow\Support\Flow;
use RefactorCircus\Showroom\Impex\Actions\UpsertProductChunk;
use RefactorCircus\Showroom\Impex\ProductChunkSummary;
use RefactorCircus\Showroom\Impex\Sources\InlineProductSource;

/**
 * `showroom:upsert-products` — product records passed in with the run: an
 * ERP push through an Impex inbound channel, or a call from code.
 *
 * Accepts a list of records, or `{"products": [...]}` as a webhook body.
 * Records are written `showroom.impex.chunk` at a time, each chunk by one
 * call to UpsertProductsAction. With `$captureChanges` false the writes are
 * not recorded for product webhooks.
 *
 * `$captureChanges` is nullable, with null meaning true, so the console
 * offers it as `--capture-changes=0` rather than as a flag that could only
 * switch it on (D-011).
 *
 * The result counts rows: `total`, `succeeded`, `failed` and `unchanged`.
 * Each failed row's reason is kept on its chunk's batch item, by its
 * position in the records sent.
 */
final class UpsertProductsFlow extends Flow
{
    /**
     * @param  array<int|string, mixed>  $payload
     * @return array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int}
     */
    public function handle(array $payload, string $mode = 'upsert', ?bool $captureChanges = null): array
    {
        $this->tag('showroom', 'upsert');

        $products = array_is_list($payload) ? $payload : (array) ($payload['products'] ?? []);

        $batch = $this->batch(InlineProductSource::class, array_values($products), $mode, $captureChanges ?? true, (int) config('showroom.impex.chunk', 500))
            ->using(UpsertProductChunk::class)
            // One chunk of records per seeded page.
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
