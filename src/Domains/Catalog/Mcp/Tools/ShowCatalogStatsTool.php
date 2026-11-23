<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Catalog\Mcp\Requests\ShowCatalogStatsMcpRequest;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogStatsResource;

#[Description('Show the catalog at a glance: products per status and how many are published, completeness per channel and locale, the 5 products waiting longest for review, and the 8 newest versions with the product or product model each belongs to.')]
final class ShowCatalogStatsTool extends Tool
{
    public function handle(ShowCatalogStatsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CatalogStatsResource::structuredSchema($schema);
    }
}
