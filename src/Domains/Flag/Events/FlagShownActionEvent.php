<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * A flag was shown.
 */
final class FlagShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FlagModel $flag,
    ) {}
}
