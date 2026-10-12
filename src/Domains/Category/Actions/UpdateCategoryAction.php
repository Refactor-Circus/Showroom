<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Category\Data\UpdateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Events\CategoryUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Category\Events\CategoryUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Support\Concerns\MovesInTree;

final class UpdateCategoryAction
{
    use MovesInTree;

    public function __construct(private readonly ProductIndex $index) {}

    /**
     * @throws ValidationException
     */
    public function execute(CategoryModel $category, UpdateCategoryData $data): CategoryModel
    {
        CategoryUpdatingActionEvent::dispatch($category, $data);

        $result = $this->perform($category, $data);

        CategoryUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CategoryModel $category, UpdateCategoryData $data): CategoryModel
    {
        return DB::transaction(function () use ($category, $data): CategoryModel {
            if ($data->has('labels')) {
                $category->labels = is_array($data->labels) ? $data->labels : [];
            }

            if (! $data->sortOrder instanceof Missing) {
                $category->sort_order = $data->sortOrder;
            }

            $category->save();

            if (! $data->parent instanceof Missing) {
                $parent = $data->parent !== null ? CategoryModel::query()->where('code', $data->parent)->firstOrFail() : null;

                if ($parent?->id !== $category->parent_id) {
                    if ($parent !== null && $category->contains($parent)) {
                        throw ValidationException::withMessages([
                            'parent' => sprintf('Category "%s" cannot move under itself or its own descendant "%s".', $category->code, $parent->code),
                        ]);
                    }

                    $this->place($category, $parent);

                    // Products beneath now sit in a different branch; a
                    // queued job lists them once this commits.
                    $this->index->queueWithin(SyncScope::Category, $category->id);
                }
            }

            return $category->load('parent');
        });
    }
}
