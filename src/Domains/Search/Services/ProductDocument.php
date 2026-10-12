<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Services;

use Illuminate\Support\Collection;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;

/**
 * The document a search index holds for a product: its identity, its own
 * and inherited values, and one block of text for full-text search.
 *
 * Values keep their storage shape — `values.color.<all_channels>.<all_locales>`
 * — with decimal amounts as numbers so ranges compare numerically.
 */
final class ProductDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function from(ProductModel $product): array
    {
        return self::many([$product])[0];
    }

    /**
     * The documents of a batch of products, in order, with what they share
     * looked up once: the attribute types of every value, and the owner and
     * category chains, read by the ids in their paths.
     *
     * @param  iterable<int, ProductModel>  $products
     * @return list<array<string, mixed>>
     */
    public static function many(iterable $products): array
    {
        $batch = [];
        $codes = [];
        $owners = [];
        $categories = [];

        foreach ($products as $product) {
            $values = $product->allValues();
            $owner = $product->effectiveOwner();
            $assigned = $product->allCategories();

            $batch[] = [$product, $values, $owner, $assigned];
            $codes += array_fill_keys(array_map('strval', array_keys($values)), true);

            foreach ($owner === null ? [] : self::pathIds($owner->path) as $id) {
                $owners[$id] = true;
            }

            foreach ($assigned as $category) {
                foreach (self::pathIds($category->path) as $id) {
                    $categories[$id] = true;
                }
            }
        }

        /** @var array<string, AttributeType> $types */
        $types = $codes === [] ? [] : AttributeModel::query()
            ->whereIn('code', array_keys($codes))
            ->get(['code', 'type'])
            ->mapWithKeys(fn (AttributeModel $attribute): array => [$attribute->code => $attribute->type])
            ->all();

        /** @var array<string, string> $ownerCodes */
        $ownerCodes = $owners === [] ? [] : OwnerModel::query()->whereKey(array_keys($owners))->pluck('code', 'id')->all();
        /** @var array<string, string> $categoryCodes */
        $categoryCodes = $categories === [] ? [] : CategoryModel::query()->whereKey(array_keys($categories))->pluck('code', 'id')->all();

        return array_map(
            fn (array $item): array => self::document($item[0], $item[1], $item[2], $item[3], $types, $ownerCodes, $categoryCodes),
            $batch,
        );
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $values
     * @param  Collection<int, CategoryModel>  $assigned
     * @param  array<string, AttributeType>  $types
     * @param  array<string, string>  $ownerCodes  By id.
     * @param  array<string, string>  $categoryCodes  By id.
     * @return array<string, mixed>
     */
    private static function document(ProductModel $product, array $values, ?OwnerModel $owner, Collection $assigned, array $types, array $ownerCodes, array $categoryCodes): array
    {
        $text = [$product->identifier];

        foreach ($values as $code => $channels) {
            foreach ($channels as $channel => $locales) {
                foreach ($locales as $locale => $data) {
                    $type = $types[$code] ?? null;

                    $values[$code][$channel][$locale] = self::indexable($type, $data);

                    if (in_array($type, [AttributeType::Text, AttributeType::Textarea, AttributeType::Select], true) && is_string($data)) {
                        $text[] = $data;
                    }
                }
            }
        }

        return [
            'id' => $product->id,
            'identifier' => $product->identifier,
            'family' => $product->family?->code,
            'parent' => $product->parent?->code,
            'owner' => $owner?->code,
            // The whole chain, so a search for a vendor finds its series' products.
            'owners' => $owner === null ? [] : self::codes($owner->path, $ownerCodes),
            'categories' => $assigned->pluck('code')->all(),
            // Assigned categories and all their ancestors, so a branch finds
            // everything filed anywhere beneath it.
            'category_tree' => $assigned
                ->flatMap(fn (CategoryModel $category): array => self::codes($category->path, $categoryCodes))
                ->unique()
                ->values()
                ->all(),
            'enabled' => $product->enabled,
            'status' => $product->status->value,
            'published' => $product->published_version !== null,
            // {channel: {locale: ratio}}, so ranges filter on completeness.
            'completeness' => $product->completeness
                ->groupBy(fn (CompletenessModel $score): string => $score->channel->code)
                ->map(fn (Collection $scores): array => $scores->mapWithKeys(fn (CompletenessModel $score): array => [$score->locale->code => $score->ratio])->all())
                ->all(),
            'values' => $values,
            'text' => implode(' ', $text),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'changed_at' => $product->changed_at?->toIso8601String(),
        ];
    }

    /**
     * The ids of a materialized path, root first.
     *
     * @return list<string>
     */
    private static function pathIds(string $path): array
    {
        return array_values(array_filter(explode('/', $path), fn (string $id): bool => $id !== ''));
    }

    /**
     * The codes of a path's nodes, root first — the node's chain().
     *
     * @param  array<string, string>  $codes  By id.
     * @return list<string>
     */
    private static function codes(string $path, array $codes): array
    {
        $chain = [];

        foreach (self::pathIds($path) as $id) {
            if (isset($codes[$id])) {
                $chain[] = $codes[$id];
            }
        }

        return $chain;
    }

    private static function indexable(?AttributeType $type, mixed $data): mixed
    {
        return match (true) {
            $type === AttributeType::Decimal && is_numeric($data) => (float) $data,
            $type === AttributeType::Metric && is_array($data) => [
                'amount' => is_numeric($data['amount'] ?? null) ? (float) $data['amount'] : null,
                'unit' => $data['unit'] ?? null,
            ],
            $type === AttributeType::Price && is_array($data) => array_map(
                fn (mixed $price): array => [
                    'amount' => is_array($price) && is_numeric($price['amount'] ?? null) ? (float) $price['amount'] : null,
                    'currency' => is_array($price) ? ($price['currency'] ?? null) : null,
                ],
                $data,
            ),
            default => $data,
        };
    }
}
