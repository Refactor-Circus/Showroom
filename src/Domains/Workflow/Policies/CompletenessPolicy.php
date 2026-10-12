<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;
use RefactorCircus\Showroom\Support\Policies\Policy;

/**
 * A completeness score belongs to its product, so each check on one defers
 * to the product's policy: reading it needs `view` on the product, changing
 * or removing it needs `update`.
 */
class CompletenessPolicy extends Policy
{
    public function view(Model $user, Model $model): bool
    {
        return $model instanceof CompletenessModel && $this->defersTo($user, 'view', $model->product);
    }

    public function update(Model $user, Model $model): bool
    {
        return $model instanceof CompletenessModel && $this->defersTo($user, 'update', $model->product);
    }

    public function delete(Model $user, Model $model): bool
    {
        return $model instanceof CompletenessModel && $this->defersTo($user, 'update', $model->product);
    }
}
