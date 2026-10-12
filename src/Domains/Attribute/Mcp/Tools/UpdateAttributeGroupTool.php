<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\UpdateAttributeGroupMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

#[Description('Update an attribute group\'s labels or sort order. The code cannot change.')]
final class UpdateAttributeGroupTool extends Tool
{
    public function handle(UpdateAttributeGroupMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'group' => $schema->string()->description('The attribute group code.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale, such as {"en": "Color", "fr": "Couleur"}. Replaces all labels when given.'),
            'sort_order' => $schema->integer()->description('Position in listings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeGroupResource::structuredSchema($schema);
    }
}
