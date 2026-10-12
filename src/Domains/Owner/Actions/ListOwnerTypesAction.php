<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnerTypesData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypesListedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypesListingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

final class ListOwnerTypesAction
{
    /**
     * @return CursorPaginator<int, OwnerTypeModel>
     */
    public function execute(ListOwnerTypesData $filters = new ListOwnerTypesData): CursorPaginator
    {
        OwnerTypesListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        OwnerTypesListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, OwnerTypeModel>
     */
    private function perform(ListOwnerTypesData $filters): CursorPaginator
    {
        $query = OwnerTypeModel::query()
            ->with('parentTypes')
            ->withCount('owners')
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListOwnerTypesData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
