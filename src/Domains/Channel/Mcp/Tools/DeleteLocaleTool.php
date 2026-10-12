<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\DeleteLocaleMcpRequest;

#[Description('Delete a locale no channel uses. Values written in it are purged.')]
final class DeleteLocaleTool extends Tool
{
    public function handle(DeleteLocaleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'locale' => $schema->string()->description('The locale code.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'deleted' => $schema->boolean()->required(),
            'code' => $schema->string()->required(),
        ];
    }
}
