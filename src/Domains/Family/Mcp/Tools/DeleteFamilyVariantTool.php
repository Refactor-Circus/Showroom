<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\DeleteFamilyVariantMcpRequest;

#[Description('Delete a family variant that no product model uses.')]
final class DeleteFamilyVariantTool extends Tool
{
    public function handle(DeleteFamilyVariantMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'family_variant' => $schema->string()->description('The family variant code.')->required(),
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
