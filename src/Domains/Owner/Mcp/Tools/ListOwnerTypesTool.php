<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnerTypesData;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Requests\ListOwnerTypesMcpRequest;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

#[Description('List owner types (manufacturer, vendor, brand, series, ...) and their chain rules. Cursor paginated.')]
final class ListOwnerTypesTool extends Tool
{
    public function handle(ListOwnerTypesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListOwnerTypesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OwnerTypeResource::collectionSchema($schema, ListOwnerTypesData::pagination());
    }
}
