<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Asset\Data\ListAssetsData;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Requests\ListAssetsMcpRequest;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

#[Description('List assets (images, documents, ...), newest first, by type or by the record they are linked to. Cursor paginated.')]
final class ListAssetsTool extends Tool
{
    public function handle(ListAssetsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code, labels or file name.'),
            'type' => $schema->string()->description('A MIME type, or a family such as image/*.'),
            'product' => $schema->string()->description('Only assets linked to this product identifier.'),
            'product_model' => $schema->string()->description('Only assets linked to this product model code.'),
            'owner' => $schema->string()->description('Only assets linked to this owner code.'),
            'role' => $schema->string()->description('With product, product_model or owner: only links in this role.'),
        ] + ListAssetsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssetResource::collectionSchema($schema, ListAssetsData::pagination());
    }
}
