<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamiliesData;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\ListFamiliesMcpRequest;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

#[Description('List families (attribute sets), the kinds of product the catalog describes, in display order. Filter by search or by an attribute they include. Cursor paginated.')]
final class ListFamiliesTool extends Tool
{
    public function handle(ListFamiliesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code or any label.'),
            'attribute' => $schema->string()->description('Only families that include this attribute code.'),
        ] + ListFamiliesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FamilyResource::collectionSchema($schema, ListFamiliesData::pagination());
    }
}
