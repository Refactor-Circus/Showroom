<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\CreateChannelMcpRequest;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

#[Description('Create a channel: where products are published, in which locales and currencies, from which category tree.')]
final class CreateChannelTool extends Tool
{
    public function handle(CreateChannelMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, such as ecommerce or print. Never changes.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'locales' => $schema->array()->description('Locale codes the channel publishes in, at least one.')->required(),
            'currencies' => $schema->array()->description('ISO 4217 codes the channel sells in, such as ["USD", "EUR"]. Scoped prices must use them.'),
            'category_tree' => $schema->string()->description('Code of the root category whose tree the channel sells, or null.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ChannelResource::structuredSchema($schema);
    }
}
