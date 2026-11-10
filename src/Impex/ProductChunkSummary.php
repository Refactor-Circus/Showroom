<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex;

use RefactorCircus\Impex\Domains\Artifact\Services\PayloadStore;
use RefactorCircus\Impex\Domains\Batch\Exceptions\BatchFailedException;
use RefactorCircus\Impex\Domains\Run\Enums\StepStatus;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Showroom\Impex\Actions\UpsertProductChunk;

/**
 * The result of an import or upsert run, counted by row.
 *
 * Impex counts a batch by its items, and each item is a chunk of records,
 * so the rows are counted here from what UpsertProductChunk kept on each
 * item. A chunk that failed as a whole (it threw, or its worker died) fails
 * every row it held.
 */
final class ProductChunkSummary
{
    public function __construct(
        private readonly Impex $impex,
        private readonly PayloadStore $payloads,
    ) {}

    /**
     * @return array{batch_id: string, total: int, succeeded: int, failed: int, unchanged: int}
     */
    public function of(string $batchId): array
    {
        $total = 0;
        $failed = 0;
        $unchanged = 0;

        foreach ($this->impex->batchItems($batchId) as $item) {
            if ($item->status === StepStatus::Completed) {
                $result = (array) $this->payloads->get($item->result, $item->result_artifact_id);

                $total += (int) ($result['total'] ?? 0);
                $failed += (int) ($result['failed'] ?? 0);
                $unchanged += (int) ($result['unchanged'] ?? 0);

                continue;
            }

            $chunk = (array) $this->payloads->get($item->payload, $item->payload_artifact_id);
            $rows = count((array) ($chunk['records'] ?? []));

            $total += $rows;
            $failed += $rows;
        }

        return [
            'batch_id' => $batchId,
            'total' => $total,
            'succeeded' => $total - $failed,
            'failed' => $failed,
            'unchanged' => $unchanged,
        ];
    }

    /**
     * Fail the run when more rows failed than the share tolerated, as Impex
     * fails a batch when more items do.
     *
     * @param  array{total: int, failed: int}  $summary
     *
     * @throws BatchFailedException
     */
    public static function tolerate(array $summary, float $share): void
    {
        $share = max(0.0, min(1.0, $share));

        if ($summary['total'] > 0 && $share < $summary['failed'] / $summary['total']) {
            throw BatchFailedException::threshold(UpsertProductChunk::class, $summary['failed'], $summary['total'], $share);
        }
    }
}
