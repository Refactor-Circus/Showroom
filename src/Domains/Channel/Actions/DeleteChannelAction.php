<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Jobs\PurgeValueSlots;

final class DeleteChannelAction
{
    public function execute(ChannelModel $channel): ChannelModel
    {
        ChannelDeletingActionEvent::dispatch($channel);

        $result = $this->perform($channel);

        ChannelDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * The values scoped to the channel are purged by a queued job.
     */
    private function perform(ChannelModel $channel): ChannelModel
    {
        return DB::transaction(function () use ($channel): ChannelModel {
            $channel->delete();

            PurgeValueSlots::dispatch(channel: $channel->code)->afterCommit();

            return $channel;
        });
    }
}
