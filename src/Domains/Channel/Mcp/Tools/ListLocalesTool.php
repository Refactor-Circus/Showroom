<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Data\ListLocalesData;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\ListLocalesMcpRequest;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

#[Description('List the locales the catalog holds content in. Cursor paginated.')]
final class ListLocalesTool extends Tool
{
    public function handle(ListLocalesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListLocalesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return LocaleResource::collectionSchema($schema, ListLocalesData::pagination());
    }
}
