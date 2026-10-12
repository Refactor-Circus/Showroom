<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Category\Data\UpdateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;

/**
 * A category is about to be updated.
 */
final class CategoryUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CategoryModel $category,
        public UpdateCategoryData $data,
    ) {}
}
