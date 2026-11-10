<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product;

use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Services\ProductChangeCapture;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Per request or job, so a batch's opt-out never leaks into the next one.
        $this->app->scoped(ProductChangeCapture::class);
    }

    public function boot(): void
    {
        // Polymorphic columns store this short name, not the class name.
        $this->keepMorphAliases([
            'showroom_product' => ProductModel::class,
        ]);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
