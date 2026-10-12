<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeGroupsData;
use RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests\ListAttributeGroupsMcpRequest;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

#[Description('List attribute groups, the sections attributes are organised into, in display order. Cursor paginated.')]
final class ListAttributeGroupsTool extends Tool
{
    public function handle(ListAttributeGroupsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListAttributeGroupsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return AttributeGroupResource::collectionSchema($schema, ListAttributeGroupsData::pagination());
    }
}
