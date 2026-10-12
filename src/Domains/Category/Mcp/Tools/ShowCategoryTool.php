<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Requests\ShowCategoryMcpRequest;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

#[Description('Show a category with its chain from the tree root, its children and how many products are filed in it.')]
final class ShowCategoryTool extends Tool
{
    public function handle(ShowCategoryMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->description('The category code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CategoryResource::structuredSchema($schema);
    }
}
