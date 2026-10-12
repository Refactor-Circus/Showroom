<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\CreateAttributeGroupMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

#[Description('Create an attribute group, a named section such as "marketing" or "technical" that attributes are organised into.')]
final class CreateAttributeGroupTool extends Tool
{
    public function handle(CreateAttributeGroupMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, starting with a letter. Never changes once created.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale, such as {"en": "Color", "fr": "Couleur"}. Replaces all labels when given.'),
            'sort_order' => $schema->integer()->description('Position in listings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeGroupResource::structuredSchema($schema);
    }
}
