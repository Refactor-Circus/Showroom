<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\ListAttributeOptionsMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

#[Description('List the options of a select or multiselect attribute, in display order. Cursor paginated.')]
final class ListAttributeOptionsTool extends Tool
{
    public function handle(ListAttributeOptionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attribute' => $schema->string()->description('The attribute code.')->required(),
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListAttributeOptionsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeOptionResource::collectionSchema($schema, ListAttributeOptionsData::pagination());
    }
}
