<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Flows;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Domains\Flow\Support\Flow;
use RefactorCircus\Showroom\Impex\Actions\UpsertAttributeOptionChunk;
use RefactorCircus\Showroom\Impex\AttributeOptionChunkSummary;
use RefactorCircus\Showroom\Impex\Sources\InlineAttributeOptionSource;

/**
 * `showroom:upsert-attribute-options` — attribute option records passed in
 * with the run: a catalog sync through an Impex inbound channel, or a call
 * from code.
 *
 * Accepts a list of records, or `{"options": [...]}` as a webhook body; any
 * other payload fails the run before a record is written. Records are
 * written `showroom.impex.chunk` at a time, each chunk by one call to
 * UpsertAttributeOptionsAction, in `$mode`, matched by attribute and code.
 *
 * The result counts rows: `total`, `succeeded`, `failed` and `unchanged`;
 * unchanged and skipped rows count inside `succeeded`. Each failed row's
 * reason is kept on its chunk's batch item, by its position in the records
 * sent.
 */
final class UpsertAttributeOptionsFlow extends Flow
{
    /**
     * @param  array<int|string, mixed>  $payload
     * @return array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int}
     *
     * @throws ValidationException When the payload is neither a list of records nor `{"options": [...]}`.
     */
    public function handle(array $payload, string $mode = 'upsert'): array
    {
        $this->tag('showroom', 'upsert-attribute-options');

        $options = $this->options($payload);

        $batch = $this->batch(InlineAttributeOptionSource::class, $options, $mode, (int) config('showroom.impex.chunk', 500))
            ->using(UpsertAttributeOptionChunk::class)
            // One chunk of records per seeded page.
            ->chunk(1)
            // Rows, not chunks, are held to the tolerated share, below.
            ->allowFailures(1.0)
            ->tries((int) config('showroom.impex.tries', 1))
            ->run();

        /** @var array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int} $summary */
        $summary = $this->sideEffect('rows', fn (): array => app(AttributeOptionChunkSummary::class)->of((string) $batch['batch_id']));

        AttributeOptionChunkSummary::tolerate($summary, (float) config('showroom.impex.allow_failures', 1.0));

        return $summary;
    }

    /**
     * The records a payload carries: the payload itself when it is a list,
     * or its `options` list.
     *
     * @param  array<int|string, mixed>  $payload
     * @return list<mixed>
     *
     * @throws ValidationException
     */
    private function options(array $payload): array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        $options = $payload['options'] ?? null;

        if (! is_array($options) || ! array_is_list($options)) {
            throw ValidationException::withMessages([
                'payload' => 'The payload must be a list of attribute option records, or {"options": [...]} with a list.',
            ]);
        }

        return $options;
    }
}
