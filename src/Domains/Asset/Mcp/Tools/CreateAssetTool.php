<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Asset\Mcp\Requests\CreateAssetMcpRequest;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

#[Description('Add an asset from a URL, or from a file already on the asset disk. Link it with attach-asset-tool.')]
final class CreateAssetTool extends Tool
{
    public function handle(CreateAssetMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: letters, digits, dots, dashes and underscores. Generated from the file name when omitted. Never changes.'),
            'labels' => $schema->object()->description('Labels (titles, alt text) keyed by locale. Replaces all labels when given.'),
            'url' => $schema->string()->description('An http(s) URL to fetch the file from. Give this or path.'),
            'path' => $schema->string()->description('The path of a file already on the asset disk, such as one uploaded straight to S3. Give this or url.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AssetResource::structuredSchema($schema);
    }
}
