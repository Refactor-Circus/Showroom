<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Product\Events\ProductDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;

final class DeleteProductAction
{
    public function __construct(private readonly ProductIndex $index) {}

    public function execute(ProductModel $product): ProductModel
    {
        ProductDeletingActionEvent::dispatch($product);

        $result = $this->perform($product);

        ProductDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Unique values go with the product; the foreign key cascades.
     */
    private function perform(ProductModel $product): ProductModel
    {
        return DB::transaction(function () use ($product): ProductModel {
            $product->delete();

            app(AssetLinks::class)->forget(ProductModel::class, [$product->id]);
            app(Associations::class)->forget(ProductModel::class, [$product->id]);
            app(Versions::class)->forget([$product->id]);

            $this->index->queue([$product->id]);

            return $product;
        });
    }
}
