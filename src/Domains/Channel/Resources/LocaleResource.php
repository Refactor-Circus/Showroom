<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * @mixin LocaleModel
 */
final class LocaleResource extends JsonResource
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
            'channels' => $schema->array()->items($schema->string())->description('Channel codes, when loaded.'),
            'channels_count' => $schema->integer()->description('When counted.'),
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
            'channels' => $this->whenLoaded('channels', fn (): array => $this->channels->pluck('code')->all()),
            'channels_count' => $this->whenCounted('channels'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
