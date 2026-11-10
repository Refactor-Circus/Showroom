<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Requests\UpdateFlagMcpRequest;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

#[Description('Update a flag\'s labels, sort order or the kinds of record it applies to. The code cannot change, and a kind of record the flag is still assigned to cannot be removed.')]
final class UpdateFlagTool extends Tool
{
    public function handle(UpdateFlagMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'flag' => $schema->string()->description('The flag code.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'applies_to' => $schema->array()->items($schema->string()->enum(FlagSubject::values()))->description('Replace the kinds of record the flag may be assigned to: "product", "owner" or both. At least one.'),
            'sort_order' => $schema->integer()->description('Position in lists, lowest first.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FlagResource::structuredSchema($schema);
    }
}
