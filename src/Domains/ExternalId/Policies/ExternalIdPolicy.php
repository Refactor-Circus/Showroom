<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ExternalId\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Support\Policies\Policy;

/**
 * An external id is part of its product or owner, and is only written and
 * read through that record's actions, so each check on one defers to the
 * record's policy: reading it needs `view` on the record, changing or
 * removing it needs `update`.
 */
class ExternalIdPolicy extends Policy
{
    public function view(Model $user, Model $model): bool
    {
        return $model instanceof ExternalIdModel && $this->defersTo($user, 'view', $model->subject);
    }

    public function update(Model $user, Model $model): bool
    {
        return $model instanceof ExternalIdModel && $this->defersTo($user, 'update', $model->subject);
    }

    public function delete(Model $user, Model $model): bool
    {
        return $model instanceof ExternalIdModel && $this->defersTo($user, 'update', $model->subject);
    }
}
