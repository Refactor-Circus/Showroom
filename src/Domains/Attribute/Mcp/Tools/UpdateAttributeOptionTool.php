<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\UpdateAttributeOptionMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

#[Description('Update an option\'s labels or sort order. The code cannot change.')]
final class UpdateAttributeOptionTool extends Tool
{
    public function handle(UpdateAttributeOptionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attribute' => $schema->string()->description('The attribute code.')->required(),
            'option' => $schema->string()->description('The option code.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale, such as {"en": "Color", "fr": "Couleur"}. Replaces all labels when given.'),
            'sort_order' => $schema->integer()->description('Position in listings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeOptionResource::structuredSchema($schema);
    }
}
