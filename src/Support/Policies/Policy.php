<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Policies\Policy as BasePolicy;

/**
 * The bundled catalog policy: any authenticated user may read and manage.
 *
 * Each policy is registered from `showroom.policies`, so an application
 * restricts the catalog by pointing a model at its own class there. A record
 * that belongs to another (an association, a version, a completeness score)
 * follows whichever policy is registered for that record through
 * `defersTo()`.
 */
abstract class Policy extends BasePolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, Model $model): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return true;
    }

    public function update(Model $user, Model $model): bool
    {
        return true;
    }

    public function delete(Model $user, Model $model): bool
    {
        return true;
    }

    /**
     * Ask the Gate about the record a child record belongs to, so the child
     * follows whichever policy is registered for it. A child whose parent is
     * gone is refused.
     */
    protected function defersTo(Model $user, string $ability, ?Model $parent): bool
    {
        return $parent !== null && $this->allowsOn($user instanceof Authenticatable ? $user : null, $ability, $parent);
    }
}
