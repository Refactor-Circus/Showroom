<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * An attribute as a member of a family, with its membership settings.
 *
 * @mixin AttributeModel
 */
final class FamilyAttributeResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'attribute' => $schema->string()->required(),
            'type' => $schema->string()->enum(array_column(AttributeType::cases(), 'value'))->required(),
            'group' => $schema->string()->nullable()->description('When loaded.'),
            'is_required' => $schema->boolean()->required(),
            'required_channels' => $schema->array()->items($schema->string())->nullable()->description('Null: required on every channel.')->required(),
            'sort_order' => $schema->integer()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pivot = $this->resource->getRelation('pivot');
        $channels = $pivot?->getAttribute('required_channels');

        return [
            'attribute' => $this->code,
            'type' => $this->type->value,
            'group' => $this->whenLoaded('group', fn (): ?string => $this->group?->code),
            'is_required' => (bool) $pivot?->getAttribute('is_required'),
            // Null: required on every channel.
            'required_channels' => is_string($channels) ? json_decode($channels, true) : $channels,
            'sort_order' => (int) $pivot?->getAttribute('sort_order'),
        ];
    }
}
