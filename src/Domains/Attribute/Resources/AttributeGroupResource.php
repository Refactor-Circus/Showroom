<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

/**
 * @mixin AttributeGroupModel
 */
final class AttributeGroupResource extends JsonResource
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
            'sort_order' => $schema->integer()->required(),
            'attributes_count' => $schema->integer()->description('When counted.'),
            'attributes' => $schema->array()->items($schema->object(AttributeResource::schema($schema)))->description('When loaded.'),
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
            'sort_order' => $this->sort_order,
            'attributes_count' => $this->whenCounted('groupedAttributes'),
            'attributes' => AttributeResource::collection($this->whenLoaded('groupedAttributes')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
