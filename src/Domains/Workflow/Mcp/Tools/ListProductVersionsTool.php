<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Workflow\Data\ListProductVersionsData;
use RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests\ListProductVersionsMcpRequest;
use RefactorCircus\Showroom\Domains\Workflow\Resources\VersionSummaryResource;

#[Description('List a product\'s history, newest first: each version\'s action, author, time and what changed. Cursor paginated.')]
final class ListProductVersionsTool extends Tool
{
    public function handle(ListProductVersionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('The product identifier.')->required(),
            'action' => $schema->string()->description('Only versions of this action: created, updated, submitted, approved, rejected, published, unpublished, archived, restored, reverted.'),
        ] + ListProductVersionsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return VersionSummaryResource::collectionSchema($schema, ListProductVersionsData::pagination());
    }
}
