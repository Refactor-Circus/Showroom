<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Channel\Mcp\Requests\CreateLocaleMcpRequest;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

#[Description('Add a locale, so localizable values can be written in it.')]
final class CreateLocaleTool extends Tool
{
    public function handle(CreateLocaleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('A language, optionally with region or script: en, en_US, zh-Hant. Never changes.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return LocaleResource::structuredSchema($schema);
    }
}
