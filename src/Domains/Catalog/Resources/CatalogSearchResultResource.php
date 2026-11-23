<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Catalog\Data\SearchHit;
use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;

/**
 * One record catalog search found. Showroom returns no links: the caller
 * builds its own from `type` and `id`.
 *
 * @mixin SearchHit
 */
final class CatalogSearchResultResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(array_column(SearchType::cases(), 'value'))->description('The kind of record.')->required(),
            'id' => $schema->string()->description('The record\'s id (a ULID).')->required(),
            'code' => $schema->string()->description('The record\'s code; a product\'s identifier.')->required(),
            'label' => $schema->string()->description('The record\'s label in the app locale, else its code; a product\'s identifier.')->required(),
            'subtitle' => $schema->string()->nullable()->description('A line beneath the label: an asset\'s filename, an owner\'s code and type, an attribute\'s code and type, else the code; null for a product.')->required(),
        ];
    }

    /**
     * @return array{type: string, id: string, code: string, label: string, subtitle: string|null}
     */
    public function toArray(Request $request): array
    {
        /** @var SearchHit $hit */
        $hit = $this->resource;

        return [
            'type' => $hit->type->value,
            'id' => $hit->id,
            'code' => $hit->code,
            'label' => $hit->label,
            'subtitle' => $hit->subtitle,
        ];
    }
}
