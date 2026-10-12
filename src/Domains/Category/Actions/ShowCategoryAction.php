<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Actions;

use RefactorCircus\Showroom\Domains\Category\Events\CategoryShowingActionEvent;
use RefactorCircus\Showroom\Domains\Category\Events\CategoryShownActionEvent;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;

final class ShowCategoryAction
{
    public function execute(CategoryModel $category): CategoryModel
    {
        CategoryShowingActionEvent::dispatch($category);

        $result = $this->perform($category);

        CategoryShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CategoryModel $category): CategoryModel
    {
        $category->load(['parent', 'children'])->loadCount(['products', 'productModels']);

        return $category->setRelation('chain', $category->chain());
    }
}
