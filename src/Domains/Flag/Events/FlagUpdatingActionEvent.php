<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Flag\Data\UpdateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * A flag is about to be updated.
 */
final class FlagUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FlagModel $flag,
        public UpdateFlagData $data,
    ) {}
}
