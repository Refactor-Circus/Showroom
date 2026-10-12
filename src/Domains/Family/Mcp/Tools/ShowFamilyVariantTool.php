<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\ShowFamilyVariantMcpRequest;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

#[Description('Show a family variant with its levels, axes, the attributes set at each level, and the common attributes set on root models.')]
final class ShowFamilyVariantTool extends Tool
{
    public function handle(ShowFamilyVariantMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'family_variant' => $schema->string()->description('The family variant code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FamilyVariantResource::structuredSchema($schema);
    }
}
