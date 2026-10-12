<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Data\UpdateProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

/**
 * A product model is about to be updated.
 */
final class ProductModelUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ProductModelModel $productModel,
        public UpdateProductModelData $data,
    ) {}
}
