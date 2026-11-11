<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Actions;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Product\Actions\UpsertProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsData;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductChunkUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductChunkUpsertingActionEvent;

/**
 * Writes one chunk of an import or upsert run with a single call to
 * UpsertProductsAction, so every rule of a single write applies, at the
 * batch's cost: one lookup, one index sync, nothing for unchanged records.
 *
 * A record that fails does not fail the chunk: it is kept in the result,
 * which Impex stores on the chunk's batch item, by its row in the source.
 */
final class UpsertProductChunk
{
    /**
     * @param  array{mode?: string, capture_changes?: bool, start?: int, records?: list<mixed>}  $chunk  As the product sources yield it.
     * @return array{total: int, created: int, updated: int, unchanged: int, skipped: int, failed: int, failures: list<array{index: int, identifier: string|null, errors: array<string, list<string>>}>}
     *
     * @throws ValidationException When the chunk's mode is not one UpsertProductsData knows.
     */
    public function execute(array $chunk): array
    {
        ProductChunkUpsertingActionEvent::dispatch($chunk);

        $result = $this->perform($chunk);

        ProductChunkUpsertedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  array{mode?: string, capture_changes?: bool, start?: int, records?: list<mixed>}  $chunk
     * @return array{total: int, created: int, updated: int, unchanged: int, skipped: int, failed: int, failures: list<array{index: int, identifier: string|null, errors: array<string, list<string>>}>}
     */
    private function perform(array $chunk): array
    {
        $records = $chunk['records'] ?? [];
        $start = (int) ($chunk['start'] ?? 0);

        // Read back from a queued payload: validated as untrusted input.
        $result = app(UpsertProductsAction::class)->execute(UpsertProductsData::fromRecords($records, [
            'mode' => $chunk['mode'] ?? 'upsert',
            'capture_changes' => $chunk['capture_changes'] ?? true,
        ]));

        return [
            'total' => count($records),
            'created' => $result->created,
            'updated' => $result->updated,
            'unchanged' => $result->unchanged,
            'skipped' => $result->skipped,
            'failed' => $result->failed(),
            // The action counts from the chunk; a reader counts from the source.
            'failures' => array_map(
                fn (array $failure): array => ['index' => $start + $failure['index']] + $failure,
                $result->failures,
            ),
        ];
    }
}
