<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\CreateAttributeMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

#[Description('Create an attribute. Its code and type are permanent. Select and multiselect attributes take options, added with create-attribute-option-tool.')]
final class CreateAttributeTool extends Tool
{
    public function handle(CreateAttributeMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, starting with a letter. Never changes once created.')->required(),
            'type' => $schema->string()->description('The attribute type: text, textarea, number, decimal, boolean, date, select, multiselect, price, or metric. Never changes once created.')->required(),
            'group' => $schema->string()->description('Code of the attribute group to put the attribute in, or null for none.'),
            'labels' => $schema->object()->description('Labels keyed by locale, such as {"en": "Color", "fr": "Couleur"}. Replaces all labels when given.'),
            'is_unique' => $schema->boolean()->description('Whether each product must hold a different value. Text, number, decimal and date only.'),
            'is_localizable' => $schema->boolean()->description('Whether values differ per locale.'),
            'is_scopable' => $schema->boolean()->description('Whether values differ per channel.'),
            'settings' => $schema->object()->description('Type-specific validation. text: max_length, regex. textarea: max_length, rich_text. number/decimal: min, max (decimal also decimals). date: min, max. price: currencies (ISO codes), decimals. metric: metric_family and default_unit, both required. Replaces all settings when given.'),
            'sort_order' => $schema->integer()->description('Position in listings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeResource::structuredSchema($schema);
    }
}
