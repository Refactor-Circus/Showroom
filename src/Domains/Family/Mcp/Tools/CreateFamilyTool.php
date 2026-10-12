<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\CreateFamilyMcpRequest;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

#[Description('Create a family: a kind of product, the attributes it has, and which of them are required. Attributes must exist first (create-attribute-tool).')]
final class CreateFamilyTool extends Tool
{
    public function handle(CreateFamilyMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, starting with a letter. Never changes once created.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale, such as {"en": "Shoes"}. Replaces all labels when given.'),
            'attributes' => $schema->array()->description('The family\'s whole attribute list, replacing any before: [{"attribute": "color", "is_required": true, "required_channels": ["ecommerce"], "sort_order": 1}]. required_channels narrows a requirement to some channels; without it, required means every channel. Order is the display order unless sort_order is given.'),
            'label_attribute' => $schema->string()->description('Code of the text attribute, in this family, whose value is a product\'s display label. Null for none.'),
            'sort_order' => $schema->integer()->description('Position in listings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FamilyResource::structuredSchema($schema);
    }
}
