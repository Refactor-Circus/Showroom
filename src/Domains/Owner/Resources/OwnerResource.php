<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Asset\Resources\LinkedAssets;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;

/**
 * @mixin OwnerModel
 */
final class OwnerResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('ULID.')->required(),
            'code' => $schema->string()->required(),
            'type' => $schema->string()->description('Owner type code, when loaded.'),
            'labels' => $schema->object()->description('Labels keyed by locale.')->required(),
            'parent' => $schema->string()->nullable()->description('Parent owner code, when loaded.'),
            'depth' => $schema->integer()->required(),
            'assets' => $schema->array()->items($schema->object([
                'code' => $schema->string()->required(),
                'role' => $schema->string()->nullable()->required(),
                'sort_order' => $schema->integer()->required(),
                'url' => $schema->string()->required(),
                'mime_type' => $schema->string()->nullable()->required(),
                'inherited' => $schema->boolean()->required(),
            ]))->description('Linked assets, when loaded.'),
            'chain' => $schema->array()->items($schema->string())->description('Owner codes from the root to this owner, when loaded.'),
            'children' => $schema->array()->items($schema->string())->description('Child owner codes, when loaded.'),
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
            'type' => $this->whenLoaded('type', fn (): string => $this->type->code),
            'labels' => $this->labels ?? [],
            'parent' => $this->whenLoaded('parent', fn (): ?string => $this->parent?->code),
            'depth' => $this->depth,
            'assets' => $this->when($this->relationLoaded('assets'), fn (): array => LinkedAssets::present($this->assets)),
            // Root first, this owner last.
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
