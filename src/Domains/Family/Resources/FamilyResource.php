<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

/**
 * @mixin FamilyModel
 */
final class FamilyResource extends JsonResource
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
            'label_attribute' => $schema->string()->nullable()->description('When loaded.'),
            'sort_order' => $schema->integer()->required(),
            'attributes_count' => $schema->integer()->description('When counted.'),
            'attributes' => $schema->array()->items($schema->object(FamilyAttributeResource::schema($schema)))->description('When loaded.'),
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
            'label_attribute' => $this->whenLoaded('labelAttribute', fn (): ?string => $this->labelAttribute?->code),
            'sort_order' => $this->sort_order,
            'attributes_count' => $this->whenCounted('familyAttributes'),
            'attributes' => FamilyAttributeResource::collection($this->whenLoaded('familyAttributes')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
