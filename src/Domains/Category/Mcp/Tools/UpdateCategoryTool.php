<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Requests\UpdateCategoryMcpRequest;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

#[Description('Update a category\'s labels or sort order, or move it with its whole branch. The code cannot change.')]
final class UpdateCategoryTool extends Tool
{
    public function handle(UpdateCategoryMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->description('The category code.')->required(),
            'parent' => $schema->string()->description('Move the category, with its branch, under this category code, or null to make it a tree of its own.'),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'sort_order' => $schema->integer()->description('Position among its siblings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CategoryResource::structuredSchema($schema);
    }
}
