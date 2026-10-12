<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeGroupsData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupsListedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupsListingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

final class ListAttributeGroupsAction
{
    /**
     * @return CursorPaginator<int, AttributeGroupModel>
     */
    public function execute(ListAttributeGroupsData $filters = new ListAttributeGroupsData): CursorPaginator
    {
        AttributeGroupsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        AttributeGroupsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, AttributeGroupModel>
     */
    private function perform(ListAttributeGroupsData $filters): CursorPaginator
    {
        $query = AttributeGroupModel::query()
            ->withCount('groupedAttributes')
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListAttributeGroupsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
