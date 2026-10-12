<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Requests\CreateCategoryMcpRequest;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

#[Description('Create a category under a parent, or a new category tree when no parent is given.')]
final class CreateCategoryTool extends Tool
{
    public function handle(CreateCategoryMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code across all trees: lowercase letters, digits and underscores. Never changes once created.')->required(),
            'parent' => $schema->string()->description('Code of the parent category. Leave out or null to start a new tree.'),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'sort_order' => $schema->integer()->description('Position among its siblings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return CategoryResource::structuredSchema($schema);
    }
}
