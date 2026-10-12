<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Actions;

use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Product\Data\ShowProductData;
use RefactorCircus\Showroom\Domains\Product\Events\ProductShowingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductShownActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

final class ShowProductAction
{
    public function execute(ProductModel $product, ShowProductData $options = new ShowProductData): ProductModel
    {
        ProductShowingActionEvent::dispatch($product);

        $result = $this->perform($product)->useValueFilter($options->valueFilter());

        ProductShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ProductModel $product): ProductModel
    {
        return $product->load(['family', 'owner', 'categories', 'assets', 'parent.assets', 'parent.parent.assets', 'parent.categories', 'parent.parent.owner', 'parent.parent.categories', 'parent.owner', 'parent.familyVariant', 'completeness.channel', 'completeness.locale', ...Associations::eagerLoads()]);
    }
}
