<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Data\RevertProductData;

/**
 * A product is about to be reverted to an earlier version.
 */
final class ProductRevertingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ProductModel $product,
        public RevertProductData $data,
    ) {}
}
