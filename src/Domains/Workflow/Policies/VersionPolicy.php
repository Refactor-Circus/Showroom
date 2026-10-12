<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Support\Policies\Policy;

/**
 * A version belongs to the product it records, so each check on one defers
 * to that product's policy: reading it needs `view` on the product, changing
 * or removing it needs `update`.
 */
class VersionPolicy extends Policy
{
    public function view(Model $user, Model $model): bool
    {
        return $model instanceof VersionModel && $this->defersTo($user, 'view', $model->versionable);
    }

    public function update(Model $user, Model $model): bool
    {
        return $model instanceof VersionModel && $this->defersTo($user, 'update', $model->versionable);
    }

    public function delete(Model $user, Model $model): bool
    {
        return $model instanceof VersionModel && $this->defersTo($user, 'update', $model->versionable);
    }
}
