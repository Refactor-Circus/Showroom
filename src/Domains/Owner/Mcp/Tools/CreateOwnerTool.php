<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Requests\CreateOwnerMcpRequest;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;

#[Description('Create an owner, such as a vendor or a series under a vendor. Its type\'s rules decide which parents are allowed.')]
final class CreateOwnerTool extends Tool
{
    public function handle(CreateOwnerMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code across all owners: letters, digits, dots, dashes and underscores. Never changes once created.')->required(),
            'type' => $schema->string()->description('The owner type code. Never changes once created.')->required(),
            'parent' => $schema->string()->description('Code of the parent owner, or null for a root owner. On update, moves the owner with everything beneath it.'),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OwnerResource::structuredSchema($schema);
    }
}
