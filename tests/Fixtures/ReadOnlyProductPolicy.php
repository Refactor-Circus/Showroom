<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Domains\Product\Policies\ProductPolicy;

/**
 * An application policy that lets anyone read products and no one change them.
 */
final class ReadOnlyProductPolicy extends ProductPolicy
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
}
