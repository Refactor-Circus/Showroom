<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\CreateAttributeOptionMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

#[Description('Add an option to a select or multiselect attribute.')]
final class CreateAttributeOptionTool extends Tool
{
    public function handle(CreateAttributeOptionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attribute' => $schema->string()->description('The attribute code.')->required(),
            'code' => $schema->string()->description('Code unique within the attribute: lowercase letters, digits and underscores. Never changes once created.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale, such as {"en": "Color", "fr": "Couleur"}. Replaces all labels when given.'),
            'sort_order' => $schema->integer()->description('Position in listings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeOptionResource::structuredSchema($schema);
    }
}
