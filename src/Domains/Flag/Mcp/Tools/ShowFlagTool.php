<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Requests\ShowFlagMcpRequest;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

#[Description('Show a flag with the kinds of record it may be assigned to.')]
final class ShowFlagTool extends Tool
{
    public function handle(ShowFlagMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'flag' => $schema->string()->description('The flag code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return FlagResource::structuredSchema($schema);
    }
}
