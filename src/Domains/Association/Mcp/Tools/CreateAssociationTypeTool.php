<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Requests\CreateAssociationTypeMcpRequest;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

#[Description('Create an association type. Products and product models are then associated through create/update-product(-model)-tool.')]
final class CreateAssociationTypeTool extends Tool
{
    public function handle(CreateAssociationTypeMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, such as cross_sell, accessories or bundle. Never changes.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'is_two_way' => $schema->boolean()->description('Whether each association also exists the other way round, kept in sync automatically: compatible parts, replacements. Fixed once created.'),
            'is_quantified' => $schema->boolean()->description('Whether each associated item carries a quantity: bundles and kits. Fixed once created; not with is_two_way.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssociationTypeResource::structuredSchema($schema);
    }
}
