<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Association\Data\ListAssociationTypesData;
use RefactorCircus\Showroom\Domains\Association\Mcp\Requests\ListAssociationTypesMcpRequest;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

#[Description('List association types (cross_sell, accessories, compatible parts, bundle, ...) and whether each is two-way or quantified. Cursor paginated.')]
final class ListAssociationTypesTool extends Tool
{
    public function handle(ListAssociationTypesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListAssociationTypesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssociationTypeResource::collectionSchema($schema, ListAssociationTypesData::pagination());
    }
}
