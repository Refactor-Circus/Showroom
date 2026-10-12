<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner;

use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

class OwnerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Polymorphic columns store this short name, not the class name.
        $this->keepMorphAliases([
            'showroom_owner' => OwnerModel::class,
        ]);

        // How the audit log (refactor-circus/keen) names them: the current locale's label, else the code.
        // Registered only while Showroom audits (see Package::audits()), which
        // is known once every provider has booted.
        $this->app->booted(function (): void {
            if (! $this->package()->audits()) {
                return;
            }

            $this->app->make(AuditHooks::class)
                ->label(OwnerModel::class, fn (OwnerModel $owner): string => $owner->label())
                ->label(OwnerTypeModel::class, fn (OwnerTypeModel $ownerType): string => $ownerType->label());
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
