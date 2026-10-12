<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Data\ListChannelsData;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\ListChannelsMcpRequest;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

#[Description('List channels (storefronts, print, marketplaces) with their locales, currencies and category tree. Cursor paginated.')]
final class ListChannelsTool extends Tool
{
    public function handle(ListChannelsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListChannelsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ChannelResource::collectionSchema($schema, ListChannelsData::pagination());
    }
}
