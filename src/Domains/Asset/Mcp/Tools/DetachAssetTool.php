<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Requests\DetachAssetMcpRequest;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

#[Description('Unlink an asset from a product, product model or owner, in one role or all.')]
final class DetachAssetTool extends Tool
{
    public function handle(DetachAssetMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset' => $schema->string()->description('The asset code.')->required(),
            'type' => $schema->string()->description('What to link: product, product_model or owner.')->required(),
            'target' => $schema->string()->description('The product identifier, or the product model or owner code.')->required(),
            'role' => $schema->string()->description('Only unlink this role; without it, every role is unlinked.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssetResource::structuredSchema($schema);
    }
}
