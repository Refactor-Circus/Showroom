<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Impex\Domains\Run\Resources\RunResource;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Requests\StartImportMcpRequest;

#[Description('Start importing products from a .csv or .jsonl file, as an Impex run. Give an existing asset code or a URL to fetch. Asynchronous: poll the run with Impex\'s show-run-tool; its result counts succeeded and failed rows.')]
final class StartImportTool extends Tool
{
    public function handle(StartImportMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'asset' => $schema->string()->description('Code of an asset holding the file.'),
            'url' => $schema->string()->description('An http(s) URL to fetch the file from; it becomes an asset first.'),
            'format' => $schema->string()->description('csv or jsonl; guessed from the file name when omitted.'),
            'mode' => $schema->string()->description('upsert (default), create (skip existing) or update (skip missing).'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RunResource::structuredSchema($schema);
    }
}
