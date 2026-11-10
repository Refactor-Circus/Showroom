<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Requests\ShowOwnerMcpRequest;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;
use RefactorCircus\Showroom\Support\LookupKey;

#[Description('Show an owner with its chain from the root, its children and how many products it owns.')]
final class ShowOwnerTool extends Tool
{
    public function handle(ShowOwnerMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'owner' => $schema->string()->description('The owner\'s id, or its slug when showroom.lookup.owners is slug or by is slug. Never its code.')->required(),
            'by' => $schema->string()->enum(array_column(LookupKey::cases(), 'value'))->description('Find the owner by this key instead of the configured one: id or slug.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OwnerResource::structuredSchema($schema);
    }
}
