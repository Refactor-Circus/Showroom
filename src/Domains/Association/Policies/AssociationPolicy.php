<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationModel;
use RefactorCircus\Showroom\Support\Policies\Policy;

/**
 * An association is part of its source product or product model, so each
 * check on one defers to the source's policy: reading it needs `view` on the
 * source, changing or removing it needs `update`.
 */
class AssociationPolicy extends Policy
{
    public function view(Model $user, Model $model): bool
    {
        return $model instanceof AssociationModel && $this->defersTo($user, 'view', $model->source);
    }

    public function update(Model $user, Model $model): bool
    {
        return $model instanceof AssociationModel && $this->defersTo($user, 'update', $model->source);
    }

    public function delete(Model $user, Model $model): bool
    {
        return $model instanceof AssociationModel && $this->defersTo($user, 'update', $model->source);
    }
}
