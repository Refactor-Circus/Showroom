<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Requests\CreateProductMcpRequest;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

#[Description('Create a product. A simple product may name a family, which limits its attributes; a variant product names its parent product model, must set every last-level axis, and differ from its siblings on them.')]
final class CreateProductTool extends Tool
{
    public function handle(CreateProductMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'identifier' => $schema->string()->description('Unique identifier (SKU): letters, digits, dots, dashes and underscores. Never changes once created.')->required(),
            'family' => $schema->string()->description('Family code. A variant product takes its family variant\'s family.'),
            'parent' => $schema->string()->description('Code of the product model this variant product belongs to: the root model of a one-level variant, or a sub-model of a two-level one.'),
            'enabled' => $schema->boolean()->description('Whether the product is enabled.'),
            'owner' => $schema->string()->description('Code of the owner (deepest in its chain, such as the series) the record belongs to, or null. Only simple products and root product models; variants take their root model\'s owner.'),
            'categories' => $schema->array()->description('The whole list of category codes to file the record in, from any trees. Replaces the list on update. Variants also inherit their models\' categories.'),
            'associations' => $schema->object()->description('Related products by association type code: {"cross_sell": {"products": ["SKU-2"], "product_models": ["tee"]}}. Each type sent replaces that type\'s targets; others stay. Two-way types are mirrored automatically.'),
            'quantified_associations' => $schema->object()->description('Bundle or kit components by quantified type code: {"bundle": {"products": [{"identifier": "SKU-3", "quantity": 2}], "product_models": []}}. Each type sent replaces that type\'s components.'),
            'values' => $schema->object()->description('Attribute values keyed by attribute code, each a list of slots: {"name": [{"locale": null, "scope": null, "data": "Classic tee"}]}. locale is required for localizable attributes and scope for scopable ones, else null. data by type: text/textarea string, number integer, decimal number or string, boolean, date "Y-m-d", select option code, multiselect list of option codes, price [{"amount": "9.99", "currency": "USD"}], metric {"amount": "1.5", "unit": "kilogram"}. On update only the slots sent change; data null clears a slot.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ProductResource::structuredSchema($schema);
    }
}
