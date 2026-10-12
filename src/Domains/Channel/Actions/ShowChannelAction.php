<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use RefactorCircus\Showroom\Domains\Channel\Events\ChannelShowingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelShownActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;

final class ShowChannelAction
{
    public function execute(ChannelModel $channel): ChannelModel
    {
        ChannelShowingActionEvent::dispatch($channel);

        $result = $this->perform($channel);

        ChannelShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ChannelModel $channel): ChannelModel
    {
        return $channel->load(['locales', 'categoryTree']);
    }
}
