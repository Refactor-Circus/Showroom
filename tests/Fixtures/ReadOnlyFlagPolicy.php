<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Domains\Flag\Policies\FlagPolicy;

/**
 * An application policy that lets anyone read flags and no one change or
 * assign them.
 */
final class ReadOnlyFlagPolicy extends FlagPolicy
{
    public function create(Model $user): bool
    {
        return false;
    }

    public function update(Model $user, Model $model): bool
    {
        return false;
    }

    public function delete(Model $user, Model $model): bool
    {
        return false;
    }

    public function assign(Model $user, ?Model $subject = null): bool
    {
        return false;
    }
}
