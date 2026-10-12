<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamilyVariantsData;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantsListedActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantsListingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

final class ListFamilyVariantsAction
{
    /**
     * @return CursorPaginator<int, FamilyVariantModel>
     */
    public function execute(ListFamilyVariantsData $filters = new ListFamilyVariantsData): CursorPaginator
    {
        FamilyVariantsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        FamilyVariantsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, FamilyVariantModel>
     */
    private function perform(ListFamilyVariantsData $filters): CursorPaginator
    {
        $query = FamilyVariantModel::query()
            ->with(['family.familyAttributes', 'variantAttributes'])
            ->orderBy('code');

        if ($filters->family !== null && $filters->family !== '') {
            $family = $filters->family;

            $query->whereHas('family', fn (Builder $families): Builder => $families->where('code', $family));
        }

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListFamilyVariantsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
