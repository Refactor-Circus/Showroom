<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ExternalId;

use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;

class ExternalIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance per request, never shared across Octane requests.
        $this->app->scoped(ExternalIds::class);
    }

    public function boot(): void
    {
        // Polymorphic columns (the audit log's) store this short name, not the
        // class name. Its rows name their product or owner by those models'
        // own aliases, showroom_product and showroom_owner.
        $this->keepMorphAliases([
            'showroom_external_id' => ExternalIdModel::class,
        ]);
    }
}
