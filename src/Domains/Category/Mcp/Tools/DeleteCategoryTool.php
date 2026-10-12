<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Category\Mcp\Requests\DeleteCategoryMcpRequest;

#[Description('Delete a category that has no children. Products filed in it are unassigned from it.')]
final class DeleteCategoryTool extends Tool
{
    public function handle(DeleteCategoryMcpRequest $request): Response|ResponseFactory
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
        return [
            'deleted' => $schema->boolean()->required(),
            'code' => $schema->string()->required(),
        ];
    }
}
