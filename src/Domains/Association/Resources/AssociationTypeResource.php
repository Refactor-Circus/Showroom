<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;

/**
 * @mixin AssociationTypeModel
 */
final class AssociationTypeResource extends JsonResource
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
            'is_two_way' => $schema->boolean()->required(),
            'is_quantified' => $schema->boolean()->required(),
            'associations_count' => $schema->integer()->description('When counted.'),
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
            'is_two_way' => $this->is_two_way,
            'is_quantified' => $this->is_quantified,
            'associations_count' => $this->whenCounted('associations'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
