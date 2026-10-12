<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Channel\Data\ListLocalesData;
use RefactorCircus\Showroom\Domains\Channel\Events\LocalesListedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\LocalesListingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

final class ListLocalesAction
{
    /**
     * @return CursorPaginator<int, LocaleModel>
     */
    public function execute(ListLocalesData $filters = new ListLocalesData): CursorPaginator
    {
        LocalesListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        LocalesListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, LocaleModel>
     */
    private function perform(ListLocalesData $filters): CursorPaginator
    {
        $query = LocaleModel::query()->withCount('channels')->orderBy('code');

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListLocalesData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
