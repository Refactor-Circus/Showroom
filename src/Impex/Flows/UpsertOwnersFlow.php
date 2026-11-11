<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Flows;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Domains\Flow\Support\Flow;
use RefactorCircus\Showroom\Impex\Actions\UpsertOwnerChunk;
use RefactorCircus\Showroom\Impex\OwnerChunkSummary;
use RefactorCircus\Showroom\Impex\Sources\InlineOwnerSource;

/**
 * `showroom:upsert-owners` — owner records passed in with the run: an ERP
 * push through an Impex inbound channel, or a call from code.
 *
 * Accepts a list of records, or `{"owners": [...]}` as a webhook body; any
 * other payload fails the run before a record is written. Records are
 * written `showroom.impex.chunk` at a time, each chunk by one call to
 * UpsertOwnersAction, in `$mode`, matched by code or by the external ids of
 * the source `$match` names. The caller sends parents before their children.
 *
 * The result counts rows: `total`, `succeeded`, `failed` and `unchanged`;
 * unchanged and skipped rows count inside `succeeded` (D-006). Each failed
 * row's reason is kept on its chunk's batch item, by its position in the
 * records sent.
 */
final class UpsertOwnersFlow extends Flow
{
    /**
     * @param  array<int|string, mixed>  $payload
     * @param  string  $match  `code`, or the source whose external ids match records to owners.
     * @return array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int}
     *
     * @throws ValidationException When the payload is neither a list of records nor `{"owners": [...]}`.
     */
    public function handle(array $payload, string $mode = 'upsert', string $match = 'code'): array
    {
        $this->tag('showroom', 'upsert-owners');

        $owners = $this->owners($payload);

        $batch = $this->batch(InlineOwnerSource::class, $owners, $mode, $match, (int) config('showroom.impex.chunk', 500))
            ->using(UpsertOwnerChunk::class)
            // One chunk of records per seeded page.
            ->chunk(1)
            // Rows, not chunks, are held to the tolerated share, below.
            ->allowFailures(1.0)
            ->tries((int) config('showroom.impex.tries', 1))
            ->run();

        /** @var array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int} $summary */
        $summary = $this->sideEffect('rows', fn (): array => app(OwnerChunkSummary::class)->of((string) $batch['batch_id']));

        OwnerChunkSummary::tolerate($summary, (float) config('showroom.impex.allow_failures', 1.0));

        return $summary;
    }

    /**
     * The records a payload carries: the payload itself when it is a list,
     * or its `owners` list.
     *
     * @param  array<int|string, mixed>  $payload
     * @return list<mixed>
     *
     * @throws ValidationException
     */
    private function owners(array $payload): array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        $owners = $payload['owners'] ?? null;

        if (! is_array($owners) || ! array_is_list($owners)) {
            throw ValidationException::withMessages([
                'payload' => 'The payload must be a list of owner records, or {"owners": [...]} with a list.',
            ]);
        }

        return $owners;
    }
}
