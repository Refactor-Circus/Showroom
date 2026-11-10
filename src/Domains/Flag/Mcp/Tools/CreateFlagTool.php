<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Requests\CreateFlagMcpRequest;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

#[Description('Create a flag: a commercial marker (restricted, pricing_only, ...) that products, owners or both can carry. An owner\'s flags carry down to everything beneath it.')]
final class CreateFlagTool extends Tool
{
    public function handle(CreateFlagMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, such as pricing_only. Never changes.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'applies_to' => $schema->array()->items($schema->string()->enum(FlagSubject::values()))->description('The kinds of record the flag may be assigned to: "product", "owner" or both. At least one.')->required(),
            'sort_order' => $schema->integer()->description('Position in lists, lowest first. Defaults to 0.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FlagResource::structuredSchema($schema);
    }
}
