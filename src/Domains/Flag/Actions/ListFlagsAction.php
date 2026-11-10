<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Flag\Data\ListFlagsData;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagsListedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagsListingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

final class ListFlagsAction
{
    /**
     * @return CursorPaginator<int, FlagModel>
     */
    public function execute(ListFlagsData $filters = new ListFlagsData): CursorPaginator
    {
        FlagsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        FlagsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, FlagModel>
     */
    private function perform(ListFlagsData $filters): CursorPaginator
    {
        $query = FlagModel::query()->orderBy('sort_order')->orderBy('code');

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListFlagsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
