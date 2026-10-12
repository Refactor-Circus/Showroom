<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset;

use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;

class AssetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // How the audit log (refactor-circus/keen) names them: the current locale's label, else the code.
        // Registered only while Showroom audits (see Package::audits()), which
        // is known once every provider has booted.
        $this->app->booted(function (): void {
            if (! $this->package()->audits()) {
                return;
            }

            $this->app->make(AuditHooks::class)
                ->label(AssetModel::class, fn (AssetModel $asset): string => $asset->label());
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
