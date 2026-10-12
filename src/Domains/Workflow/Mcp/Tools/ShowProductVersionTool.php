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
            'product' => $schema->string()->description('The product identifier.')->required(),
            'version' => $schema->string()->description('A version number, latest, or published.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return VersionResource::structuredSchema($schema);
    }
}
