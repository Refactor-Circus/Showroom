<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Requests\CreateOwnerTypeMcpRequest;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

#[Description('Create an owner type and the rules for where its owners sit in an ownership chain.')]
final class CreateOwnerTypeTool extends Tool
{
    public function handle(CreateOwnerTypeMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'code' => $schema->string()->description('Unique code: lowercase letters, digits and underscores, starting with a letter, such as vendor or series. Never changes once created.')->required(),
            'labels' => $schema->object()->description('Labels keyed by locale. Replaces all labels when given.'),
            'parent_types' => $schema->array()->description('Owner type codes allowed as parent, such as ["vendor"]. null allows any type; [] allows none.'),
            'can_be_root' => $schema->boolean()->description('Whether an owner of this type may have no parent. Default true.'),
            'owns_products' => $schema->boolean()->description('Whether products and product models may be assigned to owners of this type. Default true.'),
            'sort_order' => $schema->integer()->description('Position in listings, lowest first.')->min(0),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OwnerTypeResource::structuredSchema($schema);
    }
}
