<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ListProductModelsData;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelsListedActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelsListingActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

final class ListProductModelsAction
{
    /**
     * @return CursorPaginator<int, ProductModelModel>
     */
    public function execute(ListProductModelsData $filters = new ListProductModelsData): CursorPaginator
    {
        ProductModelsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        ProductModelsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, ProductModelModel>
     */
    private function perform(ListProductModelsData $filters): CursorPaginator
    {
        $query = ProductModelModel::query()
            // A sub-model shows its root's owner and categories with its own.
            ->with(['familyVariant', 'owner', 'categories', 'parent.owner', 'parent.categories'])
            ->orderBy('code');

        if ($filters->familyVariant !== null && $filters->familyVariant !== '') {
            $variant = $filters->familyVariant;

            $query->whereHas('familyVariant', fn (Builder $variants): Builder => $variants->where('code', $variant));
        }

        if ($filters->parent !== null && $filters->parent !== '') {
            $parent = $filters->parent;

            $query->whereHas('parent', fn (Builder $parents): Builder => $parents->where('code', $parent));
        }

        if ($filters->roots) {
            $query->whereNull('parent_id');
        }

        if ($filters->search !== null && $filters->search !== '') {
            $query->where('code', 'like', '%'.$filters->search.'%');
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListProductModelsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
