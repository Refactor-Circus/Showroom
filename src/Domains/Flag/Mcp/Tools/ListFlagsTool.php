<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Flag\Data\ListFlagsData;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Requests\ListFlagsMcpRequest;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

#[Description('List flags (restricted, pricing_only, ...): commercial markers assigned to products and owners. Cursor paginated.')]
final class ListFlagsTool extends Tool
{
    public function handle(ListFlagsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Match against the code or any label.'),
        ] + ListFlagsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FlagResource::collectionSchema($schema, ListFlagsData::pagination());
    }
}
