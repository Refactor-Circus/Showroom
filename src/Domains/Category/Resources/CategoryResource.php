<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;

/**
 * @mixin CategoryModel
 */
final class CategoryResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('ULID.')->required(),
            'code' => $schema->string()->required(),
            'labels' => $schema->object()->description('Labels keyed by locale.')->required(),
            'parent' => $schema->string()->nullable()->description('When loaded; null for a tree root.'),
            'sort_order' => $schema->integer()->required(),
            'depth' => $schema->integer()->required(),
            'chain' => $schema->array()->items($schema->string())->description('When loaded: tree root first, this category last.'),
            'children' => $schema->array()->items($schema->string())->description('When loaded.'),
            'children_count' => $schema->integer()->description('When counted.'),
            'products_count' => $schema->integer()->description('When counted.'),
            'product_models_count' => $schema->integer()->description('When counted.'),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'labels' => $this->labels ?? [],
            'parent' => $this->whenLoaded('parent', fn (): ?string => $this->parent?->code),
            'sort_order' => $this->sort_order,
            'depth' => $this->depth,
            // Tree root first, this category last.
            'chain' => $this->whenLoaded('chain', fn (): array => $this->resource->getRelation('chain')->pluck('code')->all()),
            'children' => $this->whenLoaded('children', fn (): array => $this->children->pluck('code')->all()),
            'children_count' => $this->whenCounted('children'),
            'products_count' => $this->whenCounted('products'),
            'product_models_count' => $this->whenCounted('productModels'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
