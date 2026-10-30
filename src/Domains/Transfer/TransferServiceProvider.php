<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer;

use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Impex\ImpexIntegration;

class TransferServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Imports and exports are Impex runs: offered only while Impex is active.
        if (! $this->app->make(ImpexIntegration::class)->active()) {
            return;
        }

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
