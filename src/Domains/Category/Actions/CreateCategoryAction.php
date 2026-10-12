<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Category\Data\CreateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Events\CategoryCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Category\Events\CategoryCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Support\Concerns\MovesInTree;

final class CreateCategoryAction
{
    use MovesInTree;

    public function execute(CreateCategoryData $data): CategoryModel
    {
        CategoryCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        CategoryCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateCategoryData $data): CategoryModel
    {
        return DB::transaction(function () use ($data): CategoryModel {
            $parent = $data->parent !== null ? CategoryModel::query()->where('code', $data->parent)->firstOrFail() : null;

            $category = CategoryModel::query()->create([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'sort_order' => $data->sortOrder,
                'path' => '',
            ]);

            $this->place($category, $parent);

            return $category->load('parent');
        });
    }
}
