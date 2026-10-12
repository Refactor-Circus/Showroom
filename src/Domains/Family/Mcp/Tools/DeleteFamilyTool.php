<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\DeleteFamilyMcpRequest;

#[Description('Delete a family. Its attributes are not deleted.')]
final class DeleteFamilyTool extends Tool
{
    public function handle(DeleteFamilyMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'family' => $schema->string()->description('The family code.')->required(),
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
