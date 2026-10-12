<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * @mixin AttributeModel
 */
final class AttributeResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('ULID.')->required(),
            'code' => $schema->string()->required(),
            'type' => $schema->string()->enum(array_map(fn (AttributeType $type): string => $type->value, AttributeType::cases()))->required(),
            'group' => $schema->string()->nullable()->description('The attribute group code, when loaded.'),
            'labels' => $schema->object()->description('Labels keyed by locale.')->required(),
            'is_unique' => $schema->boolean()->required(),
            'is_localizable' => $schema->boolean()->required(),
            'is_scopable' => $schema->boolean()->required(),
            'settings' => $schema->object()->description('Type-specific settings.')->required(),
            'sort_order' => $schema->integer()->required(),
            'options' => $schema->array()->items($schema->object(AttributeOptionResource::schema($schema)))->description('When loaded.'),
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
            'type' => $this->type->value,
            'group' => $this->whenLoaded('group', fn (): ?string => $this->group?->code),
            'labels' => $this->labels ?? [],
            'is_unique' => $this->is_unique,
            'is_localizable' => $this->is_localizable,
            'is_scopable' => $this->is_scopable,
            'settings' => $this->settings ?? [],
            'sort_order' => $this->sort_order,
            'options' => AttributeOptionResource::collection($this->whenLoaded('options')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
