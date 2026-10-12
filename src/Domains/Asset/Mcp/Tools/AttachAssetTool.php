<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Requests\AttachAssetMcpRequest;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

#[Description('Link an asset to a product, product model or owner under a role (image, manual, logo, ...). Variant products also show their models\' assets.')]
final class AttachAssetTool extends Tool
{
    public function handle(AttachAssetMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset' => $schema->string()->description('The asset code.')->required(),
            'type' => $schema->string()->description('What to link: product, product_model or owner.')->required(),
            'target' => $schema->string()->description('The product identifier, or the product model or owner code.')->required(),
            'role' => $schema->string()->description('What the asset is for on that record, such as image, manual or logo. Default media.'),
            'sort_order' => $schema->integer()->description('Position among the record assets in that role, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssetResource::structuredSchema($schema);
    }
}
