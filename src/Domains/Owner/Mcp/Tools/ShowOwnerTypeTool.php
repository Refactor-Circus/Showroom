<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Requests\ShowOwnerTypeMcpRequest;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

#[Description('Show an owner type with the parent types it allows.')]
final class ShowOwnerTypeTool extends Tool
{
    public function handle(ShowOwnerTypeMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'owner_type' => $schema->string()->description('The owner type code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OwnerTypeResource::structuredSchema($schema);
    }
}
