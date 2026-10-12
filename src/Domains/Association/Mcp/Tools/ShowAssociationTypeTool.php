<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Association\Mcp\Requests\ShowAssociationTypeMcpRequest;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

#[Description('Show an association type and how many associations use it.')]
final class ShowAssociationTypeTool extends Tool
{
    public function handle(ShowAssociationTypeMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'association_type' => $schema->string()->description('The association type code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssociationTypeResource::structuredSchema($schema);
    }
}
