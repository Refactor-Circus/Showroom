<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * The catalog's figures are about to be read.
 */
final class CatalogStatsShowingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;
}
