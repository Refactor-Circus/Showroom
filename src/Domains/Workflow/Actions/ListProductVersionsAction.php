<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Data\ListProductVersionsData;
use RefactorCircus\Showroom\Domains\Workflow\Events\ProductVersionsListedActionEvent;
use RefactorCircus\Showroom\Domains\Workflow\Events\ProductVersionsListingActionEvent;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;

final class ListProductVersionsAction
{
    /**
     * The product's history, newest first.
     *
     * @return CursorPaginator<int, VersionModel>
     */
    public function execute(ProductModel $product, ListProductVersionsData $filters = new ListProductVersionsData): CursorPaginator
    {
        ProductVersionsListingActionEvent::dispatch($product, $filters);

        $result = $this->perform($product, $filters);

        ProductVersionsListedActionEvent::dispatch($product, $result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, VersionModel>
     */
    private function perform(ProductModel $product, ListProductVersionsData $filters): CursorPaginator
    {
        $query = $product->versions()->getQuery()->with('author');

        if ($filters->action !== null && $filters->action !== '') {
            $query->where('action', $filters->action);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListProductVersionsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
