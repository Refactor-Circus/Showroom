<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;

/**
 * @mixin ChannelModel
 */
final class ChannelResource extends JsonResource
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
            'locales' => $schema->array()->items($schema->string())->description('Locale codes, when loaded.'),
            'currencies' => $schema->array()->items($schema->string())->required(),
            'category_tree' => $schema->string()->nullable()->description('Root category code, when loaded.'),
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
            'locales' => $this->whenLoaded('locales', fn (): array => $this->locales->pluck('code')->all()),
            'currencies' => $this->currencies ?? [],
            'category_tree' => $this->whenLoaded('categoryTree', fn (): ?string => $this->categoryTree?->code),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
