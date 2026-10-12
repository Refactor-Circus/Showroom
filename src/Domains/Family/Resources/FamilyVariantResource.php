<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

/**
 * @mixin FamilyVariantModel
 */
final class FamilyVariantResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('ULID.')->required(),
            'code' => $schema->string()->required(),
            'family' => $schema->string()->description('When loaded.'),
            'labels' => $schema->object()->description('Labels keyed by locale.')->required(),
            'levels' => $schema->array()->items($schema->object([
                'level' => $schema->integer()->required(),
                'axes' => $schema->array()->items($schema->string())->required(),
                'attributes' => $schema->array()->items($schema->string())->required(),
            ]))->description('When loaded.'),
            'common_attributes' => $schema->array()->items($schema->string())->description('When the family and its attributes are loaded.'),
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
        $codes = fn (iterable $attributes): array => collect($attributes)->map(fn (AttributeModel $attribute): string => $attribute->code)->values()->all();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'family' => $this->whenLoaded('family', fn (): string => $this->family->code),
            'labels' => $this->labels ?? [],
            'levels' => $this->whenLoaded('variantAttributes', fn (): array => array_map(
                fn (int $level): array => [
                    'level' => $level,
                    'axes' => $codes($this->resource->axesAt($level)),
                    'attributes' => $codes($this->resource->attributesAt($level)->reject(
                        fn (AttributeModel $attribute): bool => $this->resource->pivotOf($attribute)['is_axis'],
                    )),
                ],
                range(1, $this->levels),
            )),
            'common_attributes' => $this->when(
                $this->relationLoaded('family') && $this->family->relationLoaded('familyAttributes') && $this->relationLoaded('variantAttributes'),
                fn (): array => $codes($this->resource->attributesAt(0)),
            ),
            'product_models_count' => $this->whenCounted('productModels'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
