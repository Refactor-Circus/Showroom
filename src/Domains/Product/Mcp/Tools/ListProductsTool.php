<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Product\Data\ListProductsData;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Mcp\Requests\ListProductsMcpRequest;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

#[Description('Search products: full text, family, enabled, parent model, value filters, facets and sorting. Page-numbered; returns total and facets.')]
final class ListProductsTool extends Tool
{
    public function handle(ListProductsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Full-text search over the identifier and text values.'),
            'family' => $schema->string()->description('Only this family.'),
            'enabled' => $schema->boolean()->description('Only enabled, or only disabled, products.'),
            'parent' => $schema->string()->description('Only variant products under this product model code.'),
            'owner' => $schema->string()->description('Only products owned by this owner code or anything beneath it.'),
            'category' => $schema->string()->description('Only products filed in this category code or any category beneath it.'),
            'status' => $schema->string()->enum(array_column(ProductStatus::cases(), 'value'))->description('Only products in this workflow status.'),
            'published' => $schema->boolean()->description('Only products with (true) or without (false) a published version.'),
            'updated_since' => $schema->string()->description('Only products where anything they show changed at or after this ISO 8601 moment, inherited changes included (their changed_at).'),
            'complete' => $schema->object()->description('Only products at least min percent complete on a channel: {"scope": "ecommerce", "locale": "en", "min": 100}. Without locale, every locale of the channel must reach min.'),
            'filters' => $schema->array()->description('Value conditions, all of which must hold: [{"attribute": "color", "operator": "in", "value": ["red", "blue"]}, {"attribute": "weight", "operator": ">=", "value": 2}]. Operators: =, !=, in, not_in, >, >=, <, <=, empty, not_empty. Add locale/scope for localizable/scopable attributes. With the scout engine, comparisons depend on the Scout driver and empty/not_empty are unavailable. Price attributes cannot be filtered.'),
            'facets' => $schema->array()->description('Attribute codes to count values of across all matches, such as ["color", "size"]. Only a custom search engine that counts facets fills them; the bundled engines return none.'),
            'sort' => $schema->string()->description('identifier, created_at or updated_at; prefix with - for descending.'),
            'scope' => $schema->string()->description('Return only this channel\'s values (plus channel-independent ones).'),
            'locales' => $schema->array()->description('Return only these locales\' values (plus locale-independent ones), such as ["en_US"].'),
        ] + ListProductsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ProductResource::collectionSchema($schema, ListProductsData::pagination()) + [
            'facets' => $schema->object()->description('Per faceted attribute code, each value\'s count across all matches.')->required(),
        ];
    }
}
