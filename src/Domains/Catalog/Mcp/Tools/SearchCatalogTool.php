<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchCatalogData;
use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;
use RefactorCircus\Showroom\Domains\Catalog\Mcp\Requests\SearchCatalogMcpRequest;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogSearchResultResource;

#[Description('Search the catalog by a term across products (identifier prefix), assets (code, label or filename, newest first), categories, owners, families, attributes and attribute groups (code or label, by code). Answers a flat list in that type order, each hit {type, id, code, label, subtitle}, at most `limit` per type; only the types the caller may list are searched.')]
final class SearchCatalogTool extends Tool
{
    public function handle(SearchCatalogMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()->description('The term to search for, at most 191 characters.')->required(),
            'type' => $schema->string()->enum(array_column(SearchType::cases(), 'value'))->description('Search only this kind of record.'),
            'limit' => $schema->integer()->min(1)->max(SearchCatalogData::MAX_LIMIT)->description('Hits per type, '.SearchCatalogData::DEFAULT_LIMIT.' by default.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object(CatalogSearchResultResource::schema($schema)))->required(),
        ];
    }
}
