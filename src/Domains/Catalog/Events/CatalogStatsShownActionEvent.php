<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Catalog\Data\CatalogStats;

/**
 * The catalog's figures were read.
 */
final class CatalogStatsShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CatalogStats $stats,
    ) {}
}
