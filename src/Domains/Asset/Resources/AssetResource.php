<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

/**
 * @mixin AssetModel
 */
final class AssetResource extends JsonResource
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
            'filename' => $schema->string()->required(),
            'mime_type' => $schema->string()->nullable()->required(),
            'size' => $schema->integer()->description('In bytes.')->required(),
            'checksum' => $schema->string()->nullable()->required(),
            'url' => $schema->string()->required(),
            'links' => $schema->array()->items($schema->object([
                'type' => $schema->string()->enum(['product', 'product_model', 'owner'])->required(),
                'target' => $schema->string()->required(),
                'role' => $schema->string()->nullable()->required(),
                'sort_order' => $schema->integer()->required(),
            ]))->description('When the linked records are loaded.'),
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
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'url' => $this->resource->url(),
            'links' => $this->when(
                $this->relationLoaded('products') && $this->relationLoaded('productModels') && $this->relationLoaded('owners'),
                fn (): array => [
                    ...$this->links('product', $this->products),
                    ...$this->links('product_model', $this->productModels),
                    ...$this->links('owner', $this->owners),
                ],
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  iterable<int, Model>  $records
     * @return array<int, array{type: string, target: mixed, role: mixed, sort_order: mixed}>
     */
    private function links(string $type, iterable $records): array
    {
        $links = [];

        foreach ($records as $record) {
            $pivot = $record->getRelation('pivot');

            $links[] = [
                'type' => $type,
                'target' => $record instanceof ProductModel ? $record->identifier : $record->getAttribute('code'),
                'role' => $pivot instanceof Model ? $pivot->getAttribute('role') : null,
                'sort_order' => $pivot instanceof Model ? (int) $pivot->getAttribute('sort_order') : 0,
            ];
        }

        return $links;
    }
}
