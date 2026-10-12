<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\ShowAttributeMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

#[Description('Show an attribute with its group, settings and, for select and multiselect attributes, its options.')]
final class ShowAttributeTool extends Tool
{
    public function handle(ShowAttributeMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attribute' => $schema->string()->description('The attribute code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeResource::structuredSchema($schema);
    }
}
