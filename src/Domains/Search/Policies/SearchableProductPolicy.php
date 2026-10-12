<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Policies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Policies\Policy;

/**
 * A searchable product is a product read for a Scout index, so every check
 * on one is the product policy's answer for the same row.
 */
class SearchableProductPolicy extends Policy
{
    public function viewAny(Model $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', ProductModel::class);
    }

    public function view(Model $user, Model $model): bool
    {
        return $this->defersTo($user, 'view', $this->product($model));
    }

    public function create(Model $user): bool
    {
        return Gate::forUser($user)->allows('create', ProductModel::class);
    }

    public function update(Model $user, Model $model): bool
    {
        return $this->defersTo($user, 'update', $this->product($model));
    }

    public function delete(Model $user, Model $model): bool
    {
        return $this->defersTo($user, 'delete', $this->product($model));
    }

    /**
     * The product on the same row, without querying for it again.
     */
    private function product(Model $model): ProductModel
    {
        return (new ProductModel)->newFromBuilder($model->getAttributes());
    }
}
