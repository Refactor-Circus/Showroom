<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog;

use RefactorCircus\Keystone\Support\ServiceProvider;

/**
 * Read-only views across the catalog: figures and lookups that read other
 * domains' models and own none of their own.
 */
class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
