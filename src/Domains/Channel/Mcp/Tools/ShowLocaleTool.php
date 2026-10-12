<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\ShowLocaleMcpRequest;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

#[Description('Show a locale and the channels that publish in it.')]
final class ShowLocaleTool extends Tool
{
    public function handle(ShowLocaleMcpRequest $request): Response|ResponseFactory
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
        return LocaleResource::structuredSchema($schema);
    }
}
