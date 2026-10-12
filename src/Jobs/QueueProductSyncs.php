<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;

/**
 * List the products of a scope — every product, a family's, an owner's or a
 * category's subtree — and queue their syncs in chunks, so the write that
 * changed them does not walk the catalogue inside its own transaction.
 */
final class QueueProductSyncs implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public SyncScope $scope,
        public ?string $id = null,
    ) {}

    public function handle(ProductIndex $index): void
    {
        $query = $index->productsWithin($this->scope, $this->id);

        if ($query !== null) {
            $index->queueQuery($query);
        }
    }
}
