<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamilyVariantsData;
use RefactorCircus\Showroom\Domains\Family\Mcp\Requests\ListFamilyVariantsMcpRequest;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

#[Description('List family variants: how the products of a family vary (by color, then size). Cursor paginated.')]
final class ListFamilyVariantsTool extends Tool
{
    public function handle(ListFamilyVariantsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'family' => $schema->string()->description('Only this family.'),
            'search' => $schema->string()->description('Match against the code.'),
        ] + ListFamilyVariantsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FamilyVariantResource::collectionSchema($schema, ListFamilyVariantsData::pagination());
    }
}
