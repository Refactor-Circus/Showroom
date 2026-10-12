<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamiliesData;
use RefactorCircus\Showroom\Domains\Family\Events\FamiliesListedActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamiliesListingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

final class ListFamiliesAction
{
    /**
     * @return CursorPaginator<int, FamilyModel>
     */
    public function execute(ListFamiliesData $filters = new ListFamiliesData): CursorPaginator
    {
        FamiliesListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        FamiliesListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, FamilyModel>
     */
    private function perform(ListFamiliesData $filters): CursorPaginator
    {
        $query = FamilyModel::query()
            ->with('labelAttribute')
            ->withCount('familyAttributes')
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        // Which families use an attribute: what changes if the attribute does.
        if ($filters->attribute !== null && $filters->attribute !== '') {
            $attribute = $filters->attribute;

            $query->whereHas('familyAttributes', fn (Builder $attributes): Builder => $attributes->where('code', $attribute));
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListFamiliesData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
