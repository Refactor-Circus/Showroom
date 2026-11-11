<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Requests\UpdateOwnerMcpRequest;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;
use RefactorCircus\Showroom\Support\LookupKey;

#[Description('Update an owner\'s labels or enabled flag, or move it under another parent with everything beneath it. The code and type cannot change.')]
final class UpdateOwnerTool extends Tool
{
    public function handle(UpdateOwnerMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'owner' => $schema->string()->description('The owner\'s id, or its slug when showroom.lookup.owners is slug or by is slug. Never its code.')->required(),
            'by' => $schema->string()->enum(array_column(LookupKey::cases(), 'value'))->description('Find the owner by this key instead of the configured one: id or slug.'),
            'parent' => $schema->string()->description('Code of the parent owner, or null for a root owner. On update, moves the owner with everything beneath it.'),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'enabled' => $schema->boolean()->description('Whether the owner is enabled. A disabled owner switches off every product beneath it (their effective_enabled).'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OwnerResource::structuredSchema($schema);
    }
}
