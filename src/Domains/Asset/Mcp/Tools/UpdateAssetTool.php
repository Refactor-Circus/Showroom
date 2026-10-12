<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Requests\UpdateAssetMcpRequest;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

#[Description('Update an asset\'s labels, or replace its file from a URL or a disk path. The code and links stay.')]
final class UpdateAssetTool extends Tool
{
    public function handle(UpdateAssetMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset' => $schema->string()->description('The asset code.')->required(),
            'labels' => $schema->object()->description('Labels (titles, alt text) keyed by locale. Replaces all labels when given.'),
            'url' => $schema->string()->description('Replace the file with one fetched from this URL. Links and code stay.'),
            'path' => $schema->string()->description('Replace the file with one already on the asset disk. Links and code stay.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssetResource::structuredSchema($schema);
    }
}
