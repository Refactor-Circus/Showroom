<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\ShowAttributeGroupMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

#[Description('Show an attribute group with the attributes it holds.')]
final class ShowAttributeGroupTool extends Tool
{
    public function handle(ShowAttributeGroupMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'group' => $schema->string()->description('The attribute group code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeGroupResource::structuredSchema($schema);
    }
}
