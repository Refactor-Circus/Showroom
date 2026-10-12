<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Asset\Resources\LinkedAssets;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

/**
 * @mixin ProductModelModel
 */
final class ProductModelResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('ULID.')->required(),
            'code' => $schema->string()->required(),
            'family_variant' => $schema->string()->description('Family variant code, when loaded.'),
            'parent' => $schema->string()->nullable()->description('Parent product model code, when loaded.'),
            'owner' => $schema->string()->nullable()->required(),
            'categories' => $schema->array()->items($schema->string())->required(),
            'assets' => ProductResource::assetsSchema($schema)->description('When loaded.'),
            'level' => $schema->integer()->description('0 for a root model, 1 for a sub-model.')->required(),
            'values' => $schema->object()->description('Slots keyed by attribute code: [{locale, scope, data}].')->required(),
            'children' => $schema->array()->items($schema->string())->description('Sub-model codes, when loaded.'),
            'products' => $schema->array()->items($schema->string())->description('Variant product identifiers, when loaded.'),
            'associations' => $schema->object()->description('Per type code: {products, product_models}, when loaded.'),
            'quantified_associations' => $schema->object()->description('Per type code: {products, product_models} of {identifier, quantity}, when loaded.'),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $associations = $this->relationLoaded('associations') ? app(Associations::class)->present($this->resource) : null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'family_variant' => $this->whenLoaded('familyVariant', fn (): string => $this->familyVariant->code),
            'parent' => $this->whenLoaded('parent', fn (): ?string => $this->parent?->code),
            'owner' => $this->resource->rootModel()->owner?->code,
            'categories' => $this->resource->allCategories()->pluck('code')->all(),
            'assets' => $this->when($this->relationLoaded('assets'), fn (): array => LinkedAssets::present(
                $this->assets,
                $this->resource->parent?->allAssets() ?? [],
            )),
            'level' => $this->level(),
            // Own values plus the root model's, for a sub-model.
            'values' => (object) Values::toStandard($this->resource->presentedValues()),
            'children' => $this->whenLoaded('children', fn (): array => $this->children->map(fn (ProductModelModel $child): string => $child->code)->all()),
            'products' => $this->whenLoaded('products', fn (): array => $this->products->map(fn (ProductModel $product): string => $product->identifier)->all()),
            // Own and inherited from product models.
            'associations' => $this->when($associations !== null, fn (): array => $associations['associations'] ?? []),
            'quantified_associations' => $this->when($associations !== null, fn (): array => $associations['quantified_associations'] ?? []),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
