<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsResult;

/**
 * A batch of product records was upserted: what was created, updated, left
 * unchanged, skipped and refused.
 */
final class ProductsUpsertedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public UpsertProductsResult $result,
    ) {}
}
