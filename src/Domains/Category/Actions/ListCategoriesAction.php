<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Showroom\Domains\Category\Data\ListCategoriesData;
use RefactorCircus\Showroom\Domains\Category\Events\CategoriesListedActionEvent;
use RefactorCircus\Showroom\Domains\Category\Events\CategoriesListingActionEvent;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;

final class ListCategoriesAction
{
    /**
     * @return CursorPaginator<int, CategoryModel>
     */
    public function execute(ListCategoriesData $filters = new ListCategoriesData): CursorPaginator
    {
        CategoriesListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        CategoriesListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, CategoryModel>
     */
    private function perform(ListCategoriesData $filters): CursorPaginator
    {
        $query = CategoryModel::query()
            ->with('parent')
            ->withCount('children')
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($filters->roots) {
            $query->whereNull('parent_id');
        }

        if ($filters->parent !== null && $filters->parent !== '') {
            $parent = $filters->parent;

            $query->whereHas('parent', fn (Builder $parents): Builder => $parents->where('code', $parent));
        }

        if ($filters->under !== null && $filters->under !== '') {
            $ancestor = CategoryModel::query()->where('code', $filters->under)->first();

            $ancestor === null
                ? $query->whereRaw('1 = 0')
                : $query->subtreeOf($ancestor)->whereKeyNot($ancestor->getKey());
        }

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListCategoriesData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
