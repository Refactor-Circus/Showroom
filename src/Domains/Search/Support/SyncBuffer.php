<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Support;

/**
 * The product ids queued for a sync while `ProductIndex::deferring()` runs,
 * held back so they go out together. Bound scoped: each request or queued
 * job starts with none.
 */
final class SyncBuffer
{
    /**
     * @var array<string, true>|null
     */
    private ?array $ids = null;

    public function open(): bool
    {
        if ($this->ids !== null) {
            return false;
        }

        $this->ids = [];

        return true;
    }

    public function isOpen(): bool
    {
        return $this->ids !== null;
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function add(array $ids): void
    {
        foreach ($ids as $id) {
            $this->ids[$id] = true;
        }
    }

    /**
     * Close the buffer and hand back what it held.
     *
     * @return array<int, string>
     */
    public function close(): array
    {
        $ids = array_map('strval', array_keys($this->ids ?? []));
        $this->ids = null;

        return $ids;
    }
}
