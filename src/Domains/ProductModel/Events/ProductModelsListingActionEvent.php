<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ListProductModelsData;

/**
 * Product models are about to be listed.
 */
final class ProductModelsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListProductModelsData $filters,
    ) {}
}
