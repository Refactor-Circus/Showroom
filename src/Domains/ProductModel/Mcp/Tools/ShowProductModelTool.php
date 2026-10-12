<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests\ShowProductModelMcpRequest;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

#[Description('Show a product model with its values (including inherited ones), sub-models and variant products.')]
final class ShowProductModelTool extends Tool
{
    public function handle(ShowProductModelMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_model' => $schema->string()->description('The product model code.')->required(),
            'scope' => $schema->string()->description('Return only this channel\'s values (plus channel-independent ones).'),
            'locales' => $schema->array()->description('Return only these locales\' values (plus locale-independent ones), such as ["en_US"].'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ProductModelResource::structuredSchema($schema);
    }
}
