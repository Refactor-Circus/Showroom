<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Actions;

use RefactorCircus\Showroom\Domains\Product\Data\ListProductsData;
use RefactorCircus\Showroom\Domains\Product\Events\ProductsListedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductsListingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine;
use RefactorCircus\Showroom\Domains\Search\Support\ProductPage;

/**
 * Lists and searches products through the configured search engine.
 */
final class ListProductsAction
{
    public function __construct(private readonly SearchEngine $engine) {}

    public function execute(ListProductsData $filters = new ListProductsData): ProductPage
    {
        ProductsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        ProductsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Everything a listed product's resource reads, so a page costs the same
     * number of queries however many products it holds: the owner and
     * categories of the product and each model above it feed `owner` and
     * `categories`, which a variant inherits.
     *
     * @return array<int, string>
     */
    public static function eagerLoads(): array
    {
        return [
            'family.labelAttribute',
            'owner', 'categories', 'assets',
            'parent.owner', 'parent.categories', 'parent.assets',
            'parent.parent.owner', 'parent.parent.categories', 'parent.parent.assets',
            'completeness.channel', 'completeness.locale',
        ];
    }

    private function perform(ListProductsData $filters): ProductPage
    {
        $query = $filters->query();
        $results = $this->engine->search($query);

        // The engine decides the order; the database supplies the records.
        $products = ProductModel::query()
            ->with(self::eagerLoads())
            ->whereKey($results->ids)
            ->get()
            ->sortBy(fn (ProductModel $product): int|false => array_search($product->id, $results->ids, true))
            ->values()
            ->each(fn (ProductModel $product): ProductModel => $product->useValueFilter($filters->valueFilter()));

        $page = new ProductPage($products, $results->total, $query->perPage, $query->page, [
            'path' => request()->url(),
        ]);

        $page->facets = $results->facets;

        return $page;
    }
}
