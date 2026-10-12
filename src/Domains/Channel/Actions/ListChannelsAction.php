<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Channel\Data\ListChannelsData;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelsListedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelsListingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;

final class ListChannelsAction
{
    /**
     * @return CursorPaginator<int, ChannelModel>
     */
    public function execute(ListChannelsData $filters = new ListChannelsData): CursorPaginator
    {
        ChannelsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        ChannelsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, ChannelModel>
     */
    private function perform(ListChannelsData $filters): CursorPaginator
    {
        $query = ChannelModel::query()->with(['locales', 'categoryTree'])->orderBy('code');

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListChannelsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
