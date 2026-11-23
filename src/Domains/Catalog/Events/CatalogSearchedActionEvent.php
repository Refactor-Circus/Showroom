<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchCatalogData;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchHit;

/**
 * The catalog was searched.
 */
final class CatalogSearchedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Collection<int, SearchHit>  $hits
     */
    public function __construct(
        public SearchCatalogData $data,
        public Collection $hits,
    ) {}
}
