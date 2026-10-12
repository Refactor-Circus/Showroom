<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\ShowFamilyMcpRequest;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

#[Description('Show a family with its attributes, which of them are required, and its label attribute.')]
final class ShowFamilyTool extends Tool
{
    public function handle(ShowFamilyMcpRequest $request): Response|ResponseFactory
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
        return FamilyResource::structuredSchema($schema);
    }
}
