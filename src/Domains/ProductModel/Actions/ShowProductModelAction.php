<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Actions;

use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ShowProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelShowingActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelShownActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

final class ShowProductModelAction
{
    public function execute(ProductModelModel $productModel, ShowProductModelData $options = new ShowProductModelData): ProductModelModel
    {
        ProductModelShowingActionEvent::dispatch($productModel);

        $result = $this->perform($productModel)->useValueFilter($options->valueFilter());

        ProductModelShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ProductModelModel $productModel): ProductModelModel
    {
        return $productModel->load(['assets', 'parent.assets', 'familyVariant.family', 'parent.owner', 'parent.categories', 'owner', 'categories', 'children', 'products', ...Associations::eagerLoads()]);
    }
}
