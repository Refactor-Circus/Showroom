<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex;

use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Data\ValueFilter;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;

/**
 * A product as an export writes it: API shape, own and inherited data merged,
 * narrowed to a channel and locales when asked.
 */
final class ExportRecord
{
    /**
     * The working copy. Load `ExportProductPages::eagerLoads()` first, or
     * each product costs queries of its own.
     *
     * @return array<string, mixed>
     */
    public static function of(ProductModel $product, ValueFilter $filter): array
    {
        $associations = app(Associations::class)->present($product);

        return [
            'identifier' => $product->identifier,
            'family' => $product->family?->code,
            'parent' => $product->parent?->code,
            'owner' => $product->effectiveOwner()?->code,
            'enabled' => $product->enabled,
            'categories' => $product->allCategories()->map(fn (CategoryModel $category): string => $category->code)->values()->all(),
            'values' => (object) Values::toStandard($filter->apply($product->allValues())),
            'associations' => (object) $associations['associations'],
            'quantified_associations' => (object) $associations['quantified_associations'],
        ];
    }

    /**
     * The published version, or null when the product has none.
     *
     * @return array<string, mixed>|null
     */
    public static function published(ProductModel $product, ValueFilter $filter): ?array
    {
        if ($product->published_version === null) {
            return null;
        }

        return self::fromSnapshot($product, app(Versions::class)->find($product, $product->published_version)?->snapshot, $filter);
    }

    /**
     * The published version from a snapshot already loaded — an export loads
     * a page's with `Versions::publishedSnapshots()` — or null without one.
     *
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    public static function fromSnapshot(ProductModel $product, ?array $snapshot, ValueFilter $filter): ?array
    {
        if ($product->published_version === null || $snapshot === null) {
            return null;
        }

        $values = [];

        foreach ([$snapshot['inherited_values'] ?? [], $snapshot['values'] ?? []] as $standard) {
            foreach (is_array($standard) ? $standard : [] as $code => $slots) {
                foreach (is_array($slots) ? $slots : [] as $slot) {
                    $values[$code][$slot['scope'] ?? Values::ALL_CHANNELS][$slot['locale'] ?? Values::ALL_LOCALES] = $slot['data'] ?? null;
                }
            }
        }

        return [
            'identifier' => $snapshot['identifier'] ?? $product->identifier,
            'family' => $snapshot['family'] ?? null,
            'parent' => $snapshot['parent'] ?? null,
            'owner' => $snapshot['owner'] ?? null,
            'enabled' => $snapshot['enabled'] ?? true,
            'categories' => $snapshot['categories'] ?? [],
            'values' => (object) Values::toStandard($filter->apply($values)),
            'associations' => (object) ($snapshot['associations'] ?? []),
            'quantified_associations' => (object) ($snapshot['quantified_associations'] ?? []),
        ];
    }
}
