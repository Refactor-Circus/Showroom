<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Asset\Resources\LinkedAssets;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;

/**
 * @mixin ProductModel
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('ULID.')->required(),
            'identifier' => $schema->string()->required(),
            'family' => $schema->string()->nullable()->description('Family code, when loaded.'),
            'parent' => $schema->string()->nullable()->description('Parent product model code, when loaded.'),
            'owner' => $schema->string()->nullable()->required(),
            'categories' => $schema->array()->items($schema->string())->required(),
            'assets' => self::assetsSchema($schema)->description('When loaded.'),
            'enabled' => $schema->boolean()->required(),
            'status' => $schema->string()->enum(array_column(ProductStatus::cases(), 'value'))->required(),
            'published_version' => $schema->integer()->nullable()->required(),
            'published_at' => $schema->string()->nullable()->required(),
            'completeness' => $schema->array()->items($schema->object([
                'scope' => $schema->string()->required(),
                'locale' => $schema->string()->required(),
                'required' => $schema->integer()->required(),
                'missing' => $schema->integer()->required(),
                'ratio' => $schema->integer()->required(),
                'missing_attributes' => $schema->array()->items($schema->string())->required(),
            ]))->description('Per channel and locale, when loaded.'),
            'values' => $schema->object()->description('Slots keyed by attribute code: [{locale, scope, data}].')->required(),
            'associations' => $schema->object()->description('Per type code: {products, product_models}, when loaded.'),
            'quantified_associations' => $schema->object()->description('Per type code: {products, product_models} of {identifier, quantity}, when loaded.'),
            'created_at' => $schema->string()->nullable()->required(),
            'updated_at' => $schema->string()->nullable()->required(),
            'changed_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * Linked assets, own then inherited, as product and product model
     * resources list them.
     */
    public static function assetsSchema(JsonSchema $schema): Type
    {
        return $schema->array()->items($schema->object([
            'code' => $schema->string()->required(),
            'role' => $schema->string()->nullable()->required(),
            'sort_order' => $schema->integer()->required(),
            'url' => $schema->string()->nullable()->required(),
            'mime_type' => $schema->string()->nullable()->required(),
            'inherited' => $schema->boolean()->required(),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $associations = $this->relationLoaded('associations') ? app(Associations::class)->present($this->resource) : null;

        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'family' => $this->whenLoaded('family', fn (): ?string => $this->family?->code),
            'parent' => $this->whenLoaded('parent', fn (): ?string => $this->parent?->code),
            // A variant's owner is its root model's.
            'owner' => $this->resource->effectiveOwner()?->code,
            // Own and inherited, as category codes.
            'categories' => $this->resource->allCategories()->pluck('code')->all(),
            'assets' => $this->when($this->relationLoaded('assets'), fn (): array => LinkedAssets::present(
                $this->assets,
                $this->resource->parent?->allAssets() ?? [],
            )),
            'enabled' => $this->enabled,
            'status' => $this->status->value,
            // The version storefronts read, or null when unpublished.
            'published_version' => $this->published_version,
            'published_at' => $this->published_at?->toIso8601String(),
            'completeness' => $this->whenLoaded('completeness', fn (): array => $this->completeness
                ->map(fn (CompletenessModel $score): array => [
                    'scope' => $score->channel->code,
                    'locale' => $score->locale->code,
                    'required' => $score->required,
                    'missing' => $score->missing,
                    'ratio' => $score->ratio,
                    'missing_attributes' => $score->missing_attributes ?? [],
                ])
                ->sortBy(fn (array $score): string => $score['scope'].'/'.$score['locale'])
                ->values()
                ->all()),
            // Own values plus everything inherited from product models.
            'values' => (object) Values::toStandard($this->resource->presentedValues()),
            // Own and inherited from product models.
            'associations' => $this->when($associations !== null, fn (): array => $associations['associations'] ?? []),
            'quantified_associations' => $this->when($associations !== null, fn (): array => $associations['quantified_associations'] ?? []),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // When anything the product shows last changed, inherited
            // changes included. What `updated_since` filters on.
            'changed_at' => $this->changed_at?->toIso8601String(),
        ];
    }
}
