<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow;

use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance, so a revert or transition's action context reaches the
        // update it performs.
        $this->app->singleton(Versions::class);
    }

    public function boot(): void
    {
        // How the audit log (refactor-circus/keen) names a version: what it versions, and its number.
        // Registered only while Showroom audits (see Package::audits()), which
        // is known once every provider has booted.
        $this->app->booted(function (): void {
            if (! $this->package()->audits()) {
                return;
            }

            $this->app->make(AuditHooks::class)
                ->label(VersionModel::class, fn (VersionModel $version): string => $version->versionable_type.' v'.$version->version);
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
