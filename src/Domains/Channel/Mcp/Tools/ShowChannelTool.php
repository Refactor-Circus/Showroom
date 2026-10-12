<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\ShowChannelMcpRequest;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

#[Description('Show a channel with its locales, currencies and category tree.')]
final class ShowChannelTool extends Tool
{
    public function handle(ShowChannelMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'channel' => $schema->string()->description('The channel code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ChannelResource::structuredSchema($schema);
    }
}
