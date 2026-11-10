<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Requests\DeleteProductMcpRequest;
use RefactorCircus\Showroom\Support\LookupKey;

#[Description('Delete a product.')]
final class DeleteProductTool extends Tool
{
    public function handle(DeleteProductMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product' => $schema->string()->description('The product\'s id, or its slug when showroom.lookup.products is slug or by is slug. Never its identifier.')->required(),
            'by' => $schema->string()->enum(array_column(LookupKey::cases(), 'value'))->description('Find the product by this key instead of the configured one: id or slug.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'deleted' => $schema->boolean()->required(),
            'identifier' => $schema->string()->required(),
        ];
    }
}
