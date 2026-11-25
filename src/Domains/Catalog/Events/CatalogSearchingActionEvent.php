<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchCatalogData;

/**
 * The catalog is about to be searched.
 */
final class CatalogSearchingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SearchCatalogData $data,
    ) {}
}
