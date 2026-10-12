<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Concerns;

use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

/**
 * Category assignment shared by the product and product model Actions.
 */
trait AssignsCategories
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function assignCategories(ProductModel|ProductModelModel $record, array $data): void
    {
        if (! array_key_exists('categories', $data)) {
            return;
        }

        $codes = is_array($data['categories']) ? $data['categories'] : [];

        $record->categories()->sync(CategoryModel::query()->whereIn('code', $codes)->pluck('id')->all());
    }
}
