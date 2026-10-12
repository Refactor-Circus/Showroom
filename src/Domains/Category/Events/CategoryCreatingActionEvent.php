<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Category\Data\CreateCategoryData;

/**
 * A category is about to be created.
 */
final class CategoryCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CreateCategoryData $data,
    ) {}
}
