<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Sources;

use RefactorCircus\Impex\Domains\Batch\Contracts\BatchSource;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunk;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunkItem;

/**
 * Attribute option records passed in with the run — a catalog sync, an
 * inbound webhook. The records are part of the run's input, so their
 * positions are stable.
 *
 * Each batch item is a chunk of `$chunkSize` records, written by one call to
 * UpsertAttributeOptionChunk; its key, and its `start`, is its first
 * record's position in the run's input.
 */
final class InlineAttributeOptionSource implements BatchSource
{
    /**
     * @param  array<int, mixed>  $options
     */
    public function __construct(
        private readonly array $options,
        private readonly string $mode,
        private readonly int $chunkSize = 500,
    ) {}

    /**
     * @param  int  $size  How many chunks to yield.
     */
    public function chunk(?string $cursor, int $size): BatchChunk
    {
        $options = array_values($this->options);
        $total = count($options);
        $next = (int) ($cursor ?? 0);
        $items = [];

        while (count($items) < $size && $next < $total) {
            $records = array_slice($options, $next, max(1, $this->chunkSize));

            $items[] = new BatchChunkItem((string) $next, [
                'mode' => $this->mode,
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
