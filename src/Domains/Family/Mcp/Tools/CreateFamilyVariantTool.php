<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\CreateFamilyVariantMcpRequest;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

#[Description('Create a family variant: which axes a family\'s products vary on, over one or two levels, and which attributes are set at each level.')]
final class CreateFamilyVariantTool extends Tool
{
    public function handle(CreateFamilyVariantMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, starting with a letter. Never changes once created.')->required(),
            'family' => $schema->string()->description('The family code.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'levels' => $schema->array()->description('One or two levels, each with 1-5 axes (select, boolean or metric attributes of the family, not localizable or scopable) and the other attributes set at that level: [{"axes": ["color"], "attributes": ["image"]}, {"axes": ["size"], "attributes": ["sku_ean"]}]. Family attributes on no level are common, set on the root product model. Unique attributes must be on the last level.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FamilyVariantResource::structuredSchema($schema);
    }
}
