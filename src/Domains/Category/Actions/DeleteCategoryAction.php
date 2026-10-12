<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Category\Events\CategoryDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Category\Events\CategoryDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Exceptions\ModelInUseException;

final class DeleteCategoryAction
{
    public function __construct(private readonly ProductIndex $index) {}

    public function execute(CategoryModel $category): CategoryModel
    {
        CategoryDeletingActionEvent::dispatch($category);

        $result = $this->perform($category);

        CategoryDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * A category with children is refused: trees are pruned leaf first. A
     * leaf's product assignments go with it, as in any PIM.
     */
    private function perform(CategoryModel $category): CategoryModel
    {
        $children = $category->children()->count();

        if ($children > 0) {
            throw ModelInUseException::categoryHasChildren($category->code, $children);
        }

        /** @var array<int, string> $channels */
        $channels = ChannelModel::query()->where('category_tree_id', $category->id)->orderBy('code')->pluck('code')->all();

        if ($channels !== []) {
            throw ModelInUseException::categoryTreeOfChannels($category->code, $channels);
        }

        return DB::transaction(function () use ($category): CategoryModel {
            // Listed before the assignments go, a chunk at a time; the
            // syncs are sent once this commits.
            $products = $this->index->productsWithin(SyncScope::Category, $category->id);

            if ($products !== null) {
                $this->index->queueQuery($products);
            }

            $category->delete();

            return $category;
        });
    }
}
