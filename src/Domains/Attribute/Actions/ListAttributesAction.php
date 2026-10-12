<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributesData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributesListedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributesListingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

final class ListAttributesAction
{
    /**
     * @return CursorPaginator<int, AttributeModel>
     */
    public function execute(ListAttributesData $filters = new ListAttributesData): CursorPaginator
    {
        AttributesListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        AttributesListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, AttributeModel>
     */
    private function perform(ListAttributesData $filters): CursorPaginator
    {
        $query = AttributeModel::query()
            ->with('group')
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($filters->type !== null) {
            $query->where('type', $filters->type);
        }

        if ($filters->group !== null && $filters->group !== '') {
            $group = $filters->group;

            $query->whereHas('group', fn (Builder $groups): Builder => $groups->where('code', $group));
        }

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListAttributesData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
