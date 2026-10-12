<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Association\Data\ListAssociationTypesData;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypesListedActionEvent;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypesListingActionEvent;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;

final class ListAssociationTypesAction
{
    /**
     * @return CursorPaginator<int, AssociationTypeModel>
     */
    public function execute(ListAssociationTypesData $filters = new ListAssociationTypesData): CursorPaginator
    {
        AssociationTypesListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        AssociationTypesListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, AssociationTypeModel>
     */
    private function perform(ListAssociationTypesData $filters): CursorPaginator
    {
        $query = AssociationTypeModel::query()->withCount('associations')->orderBy('code');

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListAssociationTypesData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
