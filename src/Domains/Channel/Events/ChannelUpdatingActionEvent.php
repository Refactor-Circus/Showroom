<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;

/**
 * A channel is about to be updated.
 */
final class ChannelUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ChannelModel $channel,
        public UpdateChannelData $data,
    ) {}
}
