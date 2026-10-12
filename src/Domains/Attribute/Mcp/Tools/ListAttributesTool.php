<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributesData;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\ListAttributesMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

#[Description('List attributes, the typed product characteristics of the catalog, in display order. Filter by type, group, or search. Cursor paginated.')]
final class ListAttributesTool extends Tool
{
    public function handle(ListAttributesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Only attributes of this type: text, textarea, number, decimal, boolean, date, select, multiselect, price, or metric.'),
            'group' => $schema->string()->description('Only attributes in this attribute group code.'),
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListAttributesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeResource::collectionSchema($schema, ListAttributesData::pagination());
    }
}
