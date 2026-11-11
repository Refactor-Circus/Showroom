<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Actions;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpsertAttributeOptionsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Transfer\Events\AttributeOptionChunkUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\AttributeOptionChunkUpsertingActionEvent;

/**
 * Writes one chunk of an attribute option upsert run with a single call to
 * UpsertAttributeOptionsAction, so every rule of a single option write
 * applies, at the batch's cost: one lookup, one bulk insert, nothing for
 * unchanged records.
 *
 * A record that fails does not fail the chunk: it is kept in the result,
 * which Impex stores on the chunk's batch item, by its position in the
 * records sent.
 */
final class UpsertAttributeOptionChunk
{
    /**
     * @param  array{mode?: string, start?: int, records?: list<mixed>}  $chunk  As InlineAttributeOptionSource yields it.
     * @return array{total: int, created: int, updated: int, unchanged: int, skipped: int, failed: int, failures: list<array{index: int, attribute: string|null, code: string|null, errors: array<string, list<string>>}>}
     *
     * @throws ValidationException When the chunk's mode is not one UpsertAttributeOptionsData accepts.
     */
    public function execute(array $chunk): array
    {
        AttributeOptionChunkUpsertingActionEvent::dispatch($chunk);

        $result = $this->perform($chunk);

        AttributeOptionChunkUpsertedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @param  array{mode?: string, start?: int, records?: list<mixed>}  $chunk
     * @return array{total: int, created: int, updated: int, unchanged: int, skipped: int, failed: int, failures: list<array{index: int, attribute: string|null, code: string|null, errors: array<string, list<string>>}>}
     */
    private function perform(array $chunk): array
    {
        $records = $chunk['records'] ?? [];
        $start = (int) ($chunk['start'] ?? 0);

        // Read back from a queued payload: validated as untrusted input.
        $result = app(UpsertAttributeOptionsAction::class)->execute(UpsertAttributeOptionsData::from([
            'records' => $records,
            'mode' => $chunk['mode'] ?? 'upsert',
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
