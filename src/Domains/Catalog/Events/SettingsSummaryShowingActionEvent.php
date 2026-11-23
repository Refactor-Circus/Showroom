<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * The settings summary is about to be read.
 */
final class SettingsSummaryShowingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;
}
