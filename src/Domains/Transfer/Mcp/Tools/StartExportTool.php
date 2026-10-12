<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Impex\Domains\Run\Resources\RunResource;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Requests\StartExportMcpRequest;

#[Description('Start exporting products to a .jsonl or .csv file asset, as an Impex run. Takes the product search filters, plus scope and locales to narrow values. Asynchronous: the run\'s result names the asset.')]
final class StartExportTool extends Tool
{
    public function handle(StartExportMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'format' => $schema->string()->description('jsonl (default) or csv.'),
            'code' => $schema->string()->description('Code for the resulting asset; generated when omitted.'),
            'published' => $schema->boolean()->description('Write each product\'s live version and leave out unpublished products.'),
            'search' => $schema->string()->description('Full-text search.'),
            'family' => $schema->string()->description('Only this family.'),
            'category' => $schema->string()->description('Only products in this category or beneath it.'),
            'owner' => $schema->string()->description('Only products of this owner or beneath it.'),
            'status' => $schema->string()->description('Only this workflow status.'),
            'filters' => $schema->array()->description('Value conditions, as for list-products-tool.'),
            'complete' => $schema->object()->description('Completeness condition, as for list-products-tool.'),
            'scope' => $schema->string()->description('Write only this channel\'s values.'),
            'locales' => $schema->array()->description('Write only these locales\' values.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RunResource::structuredSchema($schema);
    }
}
