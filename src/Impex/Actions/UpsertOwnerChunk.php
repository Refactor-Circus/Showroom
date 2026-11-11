<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Actions;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpsertOwnersAction;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersData;
use RefactorCircus\Showroom\Domains\Transfer\Events\OwnerChunkUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\OwnerChunkUpsertingActionEvent;

/**
 * Writes one chunk of an owner upsert run with a single call to
 * UpsertOwnersAction, so every rule of a single owner write applies, at the
 * batch's cost: one lookup, one reindex, nothing for unchanged records.
 *
 * A record that fails does not fail the chunk: it is kept in the result,
 * which Impex stores on the chunk's batch item, by its position in the
 * records sent.
 */
final class UpsertOwnerChunk
{
    /**
     * @param  array{mode?: string, match?: string, start?: int, records?: list<mixed>}  $chunk  As InlineOwnerSource yields it.
     * @return array{total: int, created: int, updated: int, unchanged: int, skipped: int, failed: int, failures: list<array{index: int, code: string|null, errors: array<string, list<string>>}>}
     *
     * @throws ValidationException When the chunk's mode or match is not one UpsertOwnersData accepts.
     */
    public function execute(array $chunk): array
    {
        OwnerChunkUpsertingActionEvent::dispatch($chunk);

        $result = $this->perform($chunk);

        OwnerChunkUpsertedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  array{mode?: string, match?: string, start?: int, records?: list<mixed>}  $chunk
     * @return array{total: int, created: int, updated: int, unchanged: int, skipped: int, failed: int, failures: list<array{index: int, code: string|null, errors: array<string, list<string>>}>}
     */
    private function perform(array $chunk): array
    {
        $records = $chunk['records'] ?? [];
        $start = (int) ($chunk['start'] ?? 0);

        // Read back from a queued payload: validated as untrusted input.
        $result = app(UpsertOwnersAction::class)->execute(UpsertOwnersData::from([
            'records' => $records,
            'mode' => $chunk['mode'] ?? 'upsert',
            'match' => $chunk['match'] ?? 'code',
        ]));

        return [
            'total' => count($records),
            'created' => $result->created,
            'updated' => $result->updated,
            'unchanged' => $result->unchanged,
            'skipped' => $result->skipped,
            'failed' => $result->failed(),
            // The action counts from the chunk; a reader counts from the records sent.
            'failures' => array_map(
                fn (array $failure): array => ['index' => $start + $failure['index']] + $failure,
                $result->failures,
            ),
        ];
    }
}
