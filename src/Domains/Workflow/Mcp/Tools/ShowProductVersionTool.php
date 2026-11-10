<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests\ShowProductVersionMcpRequest;
use RefactorCircus\Showroom\Domains\Workflow\Resources\VersionResource;
use RefactorCircus\Showroom\Support\LookupKey;

#[Description('Show one version of a product with its full snapshot: a version number, latest, or published for the content storefronts read.')]
final class ShowProductVersionTool extends Tool
{
    public function handle(ShowProductVersionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('The product\'s id, or its slug when showroom.lookup.products is slug or by is slug. Never its identifier.')->required(),
            'by' => $schema->string()->enum(array_column(LookupKey::cases(), 'value'))->description('Find the product by this key instead of the configured one: id or slug.'),
            'version' => $schema->string()->description('A version number, latest, or published.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return VersionResource::structuredSchema($schema);
    }
}
