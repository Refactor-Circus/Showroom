<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;
use RefactorCircus\Showroom\Support\KeyNormalizer;

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
     * looked up once: the attribute types of every value, the owner and
     * category chains, read by the ids in their paths, and the effective flags.
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

        /** @var EloquentCollection<int, ProductModel> $products */
        $products = new EloquentCollection(is_array($products) ? array_values($products) : iterator_to_array($products, false));

        // One query for the external ids of every product not loaded with them.
        app(ExternalIds::class)->load($products);

        // One query for the effective flags of the whole batch.
        $flags = app(Flags::class)->effective($products);

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

        $chain = $owners === [] ? new Collection : OwnerModel::query()->whereKey(array_keys($owners))->get(['id', 'code', 'slug']);
        /** @var array<string, string> $ownerCodes */
        $ownerCodes = $chain->pluck('code', 'id')->all();
        /** @var array<string, string> $ownerSlugs */
        $ownerSlugs = $chain->pluck('slug', 'id')->all();
        /** @var array<string, string> $categoryCodes */
        $categoryCodes = $categories === [] ? [] : CategoryModel::query()->whereKey(array_keys($categories))->pluck('code', 'id')->all();

        return array_map(
            fn (array $item): array => self::document($item[0], $item[1], $item[2], $item[3], $types, $ownerCodes, $ownerSlugs, $categoryCodes, $flags[$item[0]->id] ?? []),
            $batch,
        );
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $values
     * @param  Collection<int, CategoryModel>  $assigned
     * @param  array<string, AttributeType>  $types
     * @param  array<string, string>  $ownerCodes  By id.
     * @param  array<string, string>  $ownerSlugs  By id.
     * @param  array<string, string>  $categoryCodes  By id.
     * @param  list<string>  $flags  The product's effective flag codes.
     * @return array<string, mixed>
     */
    private static function document(ProductModel $product, array $values, ?OwnerModel $owner, Collection $assigned, array $types, array $ownerCodes, array $ownerSlugs, array $categoryCodes, array $flags): array
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
            'slug' => $product->slug,
            'family' => $product->family?->code,
            'parent' => $product->parent?->code,
            'owner' => $owner?->code,
            // The whole chain, so a search for a vendor finds its series' products.
            'owners' => $owner === null ? [] : self::codes($owner->path, $ownerCodes),
            // The same chain by slug, root first.
            'owner_slugs' => $owner === null ? [] : self::codes($owner->path, $ownerSlugs),
            'categories' => $assigned->pluck('code')->all(),
            // Assigned categories and all their ancestors, so a branch finds
            // everything filed anywhere beneath it.
            'category_tree' => $assigned
                ->flatMap(fn (CategoryModel $category): array => self::codes($category->path, $categoryCodes))
                ->unique()
                ->values()
                ->all(),
            // "{source}:{id}", the id normalized, so a filter matches it
            // whatever its case or accents, as the database does.
            'external_ids' => self::externalIds($product),
            // Effective flags — own, the owner chain's, a variant's root model
            // owner's — normalized, so a filter matches whatever its case.
            'flags' => array_map(self::flag(...), $flags),
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
     * The value a document stores, and a filter matches, for one external id.
     */
    public static function externalId(string $source, string $externalId): string
    {
        return $source.':'.KeyNormalizer::normalize($externalId);
    }

    /**
     * The value a document stores, and a filter matches, for one flag code.
     */
    public static function flag(string $code): string
    {
        return KeyNormalizer::normalize($code);
    }

    /**
     * The product's external ids as "{source}:{id}", by source.
     *
     * @return list<string>
     */
    private static function externalIds(ProductModel $product): array
    {
        $ids = [];

        foreach (ExternalIds::present($product->externalIds) as $source => $externalId) {
            // A digits-only source name comes back as an int key.
            $ids[] = self::externalId((string) $source, $externalId);
        }

        return $ids;
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
     * The codes (or slugs) of a path's nodes, root first — the node's chain().
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
