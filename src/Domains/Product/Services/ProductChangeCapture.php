<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Services;

use Closure;

/**
 * Whether product writes are recorded for product webhooks right now.
 *
 * They are, unless a write runs inside suppress(): a batch upsert sent with
 * `capture_changes: false` does, so a host can reload its catalog without
 * telling every subscriber. Whatever records changes (the Impex integration's
 * CaptureProductChanges) asks isSuppressed() first; core never names it.
 *
 * Bound scoped: each request or queued job starts capturing.
 */
final class ProductChangeCapture
{
    private bool $suppressed = false;

    /**
     * Run a callback with change capture off, then put back whatever was
     * set before, even when the callback throws.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function suppress(Closure $callback): mixed
    {
        $previous = $this->suppressed;
        $this->suppressed = true;

        try {
            return $callback();
        } finally {
            $this->suppressed = $previous;
        }
    }

    public function isSuppressed(): bool
    {
        return $this->suppressed;
    }
}
