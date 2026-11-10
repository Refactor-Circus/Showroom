<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Sources;

use RefactorCircus\Impex\Domains\Batch\Contracts\BatchSource;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunk;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunkItem;

/**
 * Owner records passed in with the run — an ERP push, an inbound webhook.
 * The records are part of the run's input, so their positions are stable.
 *
 * Each batch item is a chunk of `$chunkSize` records, written by one call to
 * UpsertOwnerChunk; its key, and its `start`, is its first record's position
 * in the run's input.
 */
final class InlineOwnerSource implements BatchSource
{
    /**
     * @param  array<int, mixed>  $owners
     * @param  string  $match  `code`, or the source whose external ids match records to owners.
     */
    public function __construct(
        private readonly array $owners,
        private readonly string $mode,
        private readonly string $match = 'code',
        private readonly int $chunkSize = 500,
    ) {}

    /**
     * @param  int  $size  How many chunks to yield.
     */
    public function chunk(?string $cursor, int $size): BatchChunk
    {
        $owners = array_values($this->owners);
        $total = count($owners);
        $next = (int) ($cursor ?? 0);
        $items = [];

        while (count($items) < $size && $next < $total) {
            $records = array_slice($owners, $next, max(1, $this->chunkSize));

            $items[] = new BatchChunkItem((string) $next, [
                'mode' => $this->mode,
                'match' => $this->match,
                'start' => $next,
                'records' => $records,
            ]);

            $next += count($records);
        }

        return $next >= $total
            ? BatchChunk::last($items)
            : BatchChunk::of($items, (string) $next);
    }
}
