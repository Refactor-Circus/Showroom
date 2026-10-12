<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnersData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnersListedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnersListingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;

final class ListOwnersAction
{
    /**
     * @return CursorPaginator<int, OwnerModel>
     */
    public function execute(ListOwnersData $filters = new ListOwnersData): CursorPaginator
    {
        OwnersListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        OwnersListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, OwnerModel>
     */
    private function perform(ListOwnersData $filters): CursorPaginator
    {
        $query = OwnerModel::query()
            ->with(['type', 'parent'])
            ->withCount('children')
            ->orderBy('code');

        if ($filters->type !== null && $filters->type !== '') {
            $type = $filters->type;

            $query->whereHas('type', fn (Builder $types): Builder => $types->where('code', $type));
        }

        if ($filters->parent !== null && $filters->parent !== '') {
            $parent = $filters->parent;

            $query->whereHas('parent', fn (Builder $parents): Builder => $parents->where('code', $parent));
        }

        if ($filters->under !== null && $filters->under !== '') {
            $ancestor = OwnerModel::query()->where('code', $filters->under)->first();

            $ancestor === null
                ? $query->whereRaw('1 = 0')
                : $query->subtreeOf($ancestor)->whereKeyNot($ancestor->getKey());
        }

        if ($filters->roots) {
            $query->whereNull('parent_id');
        }

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListOwnersData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
