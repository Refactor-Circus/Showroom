<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/**
 * @mixin OwnerTypeModel
 */
final class OwnerTypeResource extends JsonResource
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
            'parent_types' => $schema->array()->items($schema->string())->nullable()->description('Owner type codes allowed as parent; null for any. When loaded.'),
            'can_be_root' => $schema->boolean()->required(),
            'owns_products' => $schema->boolean()->required(),
            'sort_order' => $schema->integer()->required(),
            'owners_count' => $schema->integer()->description('When counted.'),
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
            // null: any owner type may be the parent.
            'parent_types' => $this->whenLoaded('parentTypes', fn (): ?array => $this->restricts_parents
                ? $this->parentTypes->pluck('code')->all()
                : null),
            'can_be_root' => $this->can_be_root,
            'owns_products' => $this->owns_products,
            'sort_order' => $this->sort_order,
            'owners_count' => $this->whenCounted('owners'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
