<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests\RevertProductMcpRequest;

#[Description('Restore a product\'s values, categories, associations, family, owner and enabled flag from an earlier version, recorded as a new version.')]
final class RevertProductTool extends Tool
{
    public function handle(RevertProductMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('The product identifier.')->required(),
            'version' => $schema->integer()->description('The version number to restore.')->min(1)->required(),
            'comment' => $schema->string()->description('Why, recorded in the history.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ProductResource::structuredSchema($schema);
    }
}
