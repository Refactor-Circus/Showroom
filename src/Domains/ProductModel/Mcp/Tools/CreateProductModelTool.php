<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests\CreateProductModelMcpRequest;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

#[Description('Create a product model. A root model names its family variant and sets the common attributes; a sub-model names its parent and sets level-1 attributes, including every level-1 axis.')]
final class CreateProductModelTool extends Tool
{
    public function handle(CreateProductModelMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: letters, digits, dots, dashes and underscores. Never changes once created.')->required(),
            'family_variant' => $schema->string()->description('Code of the family variant, for a root model. A sub-model takes its parent\'s.'),
            'parent' => $schema->string()->description('Code of the root model, to create a sub-model of a two-level family variant.'),
            'owner' => $schema->string()->description('Code of the owner (deepest in its chain, such as the series) the record belongs to, or null. Only simple products and root product models; variants take their root model\'s owner.'),
            'categories' => $schema->array()->description('The whole list of category codes to file the record in, from any trees. Replaces the list on update. Variants also inherit their models\' categories.'),
            'associations' => $schema->object()->description('Related products by association type code: {"cross_sell": {"products": ["SKU-2"], "product_models": ["tee"]}}. Each type sent replaces that type\'s targets; others stay. Two-way types are mirrored automatically.'),
            'quantified_associations' => $schema->object()->description('Bundle or kit components by quantified type code: {"bundle": {"products": [{"identifier": "SKU-3", "quantity": 2}], "product_models": []}}. Each type sent replaces that type\'s components.'),
            'values' => $schema->object()->description('Attribute values keyed by attribute code, each a list of slots: {"name": [{"locale": null, "scope": null, "data": "Classic tee"}]}. locale is required for localizable attributes and scope for scopable ones, else null. data by type: text/textarea string, number integer, decimal number or string, boolean, date "Y-m-d", select option code, multiselect list of option codes, price [{"amount": "9.99", "currency": "USD"}], metric {"amount": "1.5", "unit": "kilogram"}. On update only the slots sent change; data null clears a slot.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ProductModelResource::structuredSchema($schema);
    }
}
