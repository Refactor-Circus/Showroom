<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\UpdateChannelMcpRequest;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

#[Description('Update a channel\'s labels, locales, currencies or category tree. The code cannot change.')]
final class UpdateChannelTool extends Tool
{
    public function handle(UpdateChannelMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'channel' => $schema->string()->description('The channel code.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'locales' => $schema->array()->description('Replace the locale codes the channel publishes in, at least one.'),
            'currencies' => $schema->array()->description('ISO 4217 codes the channel sells in, such as ["USD", "EUR"]. Scoped prices must use them.'),
            'category_tree' => $schema->string()->description('Code of the root category whose tree the channel sells, or null.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ChannelResource::structuredSchema($schema);
    }
}
