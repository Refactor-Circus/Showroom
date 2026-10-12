<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Webhooks;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Tables;
use stdClass;

/**
 * Loads products as subscribers see them: the published version's data,
 * with the categories, owner and assets the product has now.
 *
 * Attribute values, family and associations come from the published
 * version, so a draft edit never leaves Showroom. Categorisation and assets
 * are not versioned, so they follow the live product; a category change
 * reaches subscribers once the product is published, and an asset change as
 * soon as it is linked.
 *
 * A chunk of any size costs the same seven queries — the products and their
 * versions, two levels of models, categories for products and for models,
 * owners, assets — never one per product.
 */
final class ProductSnapshots
{
    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * Published products by identifier. A product with no published version,
     * or none at all, is null.
     *
     * @param  list<string>  $identifiers
     * @return array<string, array<string, mixed>|null>
     */
    public function load(array $identifiers): array
    {
        $snapshots = array_fill_keys($identifiers, null);

        if ($identifiers === []) {
            return $snapshots;
        }

        $products = $this->products($identifiers);

        if ($products === []) {
            return $snapshots;
        }

        $models = $this->models(array_values(array_filter(array_column($products, 'parent_id'))));
        $productIds = array_column($products, 'id');
        $modelIds = array_keys($models);
        $categories = $this->categories($productIds, $modelIds);
        $assets = $this->assets($productIds, $modelIds);

        $ownerIds = [];

        foreach ($products as $product) {
            $ownerIds[] = $this->ownerId($product, $models);
        }

        $owners = $this->owners(array_values(array_filter($ownerIds)));

        foreach ($products as $product) {
            $chain = $this->chain($product['parent_id'], $models);
            $owner = $owners[$this->ownerId($product, $models) ?? ''] ?? null;

            /** @var array<string, mixed> $published */
            $published = json_decode($product['snapshot'], true) ?: [];

            $categorised = [];

            foreach ([$product['id'], ...array_keys($chain)] as $id) {
                foreach ($categories[$id] ?? [] as $code => $path) {
                    $categorised[$code] = $path;
                }
            }

            ksort($categorised);

            $linked = [];

            foreach ([$product['id'], ...array_keys($chain)] as $id) {
                foreach ($assets[$id] ?? [] as $asset) {
                    $linked[] = $asset;
                }
            }

            $snapshots[$product['identifier']] = [
                'identifier' => $product['identifier'],
                'version' => $product['version'],
                'family' => $published['family'] ?? null,
                'parent' => $published['parent'] ?? null,
                'owner' => $owner['code'] ?? null,
                'enabled' => $published['enabled'] ?? true,
                'categories' => array_keys($categorised),
                'values' => $this->values($published),
                'associations' => $published['associations'] ?? [],
                'quantified_associations' => $published['quantified_associations'] ?? [],
                'assets' => $linked,
                // What subscriptions are matched on; never sent.
                '_scope' => [
                    'categories' => array_values($categorised),
                    'owner' => $owner['path'] ?? null,
                    'family' => $published['family'] ?? null,
                    'models' => array_values(array_map(fn (array $model): string => $model['code'], $chain)),
                ],
            ];
        }

        return $snapshots;
    }

    /**
     * @param  list<string>  $identifiers
     * @return list<array{id: string, identifier: string, parent_id: string|null, owner_id: string|null, version: int, snapshot: string}>
     */
    private function products(array $identifiers): array
    {
        $morph = (new ProductModel)->getMorphClass();

        return array_values($this->db->table(Tables::name('products').' as p')
            ->join(Tables::name('versions').' as v', function (JoinClause $join) use ($morph): void {
                $join->on('v.versionable_id', '=', 'p.id')
                    ->on('v.version', '=', 'p.published_version')
                    ->where('v.versionable_type', '=', $morph);
            })
            ->whereIn('p.identifier', $identifiers)
            ->whereNotNull('p.published_version')
            ->get(['p.id', 'p.identifier', 'p.parent_id', 'p.owner_id', 'v.version', 'v.snapshot'])
            ->map(function (mixed $row): array {
                /** @var stdClass $row */
                return [
                    'id' => (string) $row->id,
                    'identifier' => (string) $row->identifier,
                    'parent_id' => is_string($row->parent_id) ? $row->parent_id : null,
                    'owner_id' => is_string($row->owner_id) ? $row->owner_id : null,
                    'version' => (int) $row->version,
                    'snapshot' => (string) $row->snapshot,
                ];
            })
            ->all());
    }

    /**
     * Models two levels up: a variant's model and that model's parent.
     *
     * @param  list<string>  $ids
     * @return array<string, array{code: string, parent_id: string|null, owner_id: string|null}>
     */
    private function models(array $ids): array
    {
        $models = [];
        $pending = array_values(array_unique($ids));

        for ($level = 0; $level < 2 && $pending !== []; $level++) {
            $next = [];

            foreach ($this->db->table(Tables::name('product_models'))->whereIn('id', $pending)->get(['id', 'code', 'parent_id', 'owner_id']) as $row) {
                /** @var stdClass $row */
                $models[(string) $row->id] = [
                    'code' => (string) $row->code,
                    'parent_id' => is_string($row->parent_id) ? $row->parent_id : null,
                    'owner_id' => is_string($row->owner_id) ? $row->owner_id : null,
                ];

                if (is_string($row->parent_id)) {
                    $next[] = $row->parent_id;
                }
            }

            $pending = array_values(array_unique(array_diff($next, array_keys($models))));
        }

        return $models;
    }

    /**
     * A product's models, nearest first, keyed by id.
     *
     * @param  array<string, array{code: string, parent_id: string|null, owner_id: string|null}>  $models
     * @return array<string, array{code: string, parent_id: string|null, owner_id: string|null}>
     */
    private function chain(?string $parentId, array $models): array
    {
        $chain = [];

        while ($parentId !== null && isset($models[$parentId]) && ! isset($chain[$parentId])) {
            $chain[$parentId] = $models[$parentId];
            $parentId = $models[$parentId]['parent_id'];
        }

        return $chain;
    }

    /**
     * A variant takes its root model's owner; a simple product has its own.
     *
     * @param  array{parent_id: string|null, owner_id: string|null}  $product
     * @param  array<string, array{code: string, parent_id: string|null, owner_id: string|null}>  $models
     */
    private function ownerId(array $product, array $models): ?string
    {
        $chain = $this->chain($product['parent_id'], $models);

        if ($chain === []) {
            return $product['owner_id'];
        }

        $root = end($chain);

        return $root['owner_id'];
    }

    /**
     * Category codes and paths, by product or model id.
     *
     * @param  list<string>  $productIds
     * @param  list<string>  $modelIds
     * @return array<string, array<string, string>>
     */
    private function categories(array $productIds, array $modelIds): array
    {
        $categories = [];

        foreach ([[Tables::name('category_product'), 'product_id', $productIds], [Tables::name('category_product_model'), 'product_model_id', $modelIds]] as [$table, $column, $ids]) {
            if ($ids === []) {
                continue;
            }

            $rows = $this->db->table($table.' as link')
                ->join(Tables::name('categories').' as c', 'c.id', '=', 'link.category_id')
                ->whereIn('link.'.$column, $ids)
                ->get(['link.'.$column.' as owner', 'c.code', 'c.path']);

            foreach ($rows as $row) {
                /** @var stdClass $row */
                $categories[(string) $row->owner][(string) $row->code] = (string) $row->path;
            }
        }

        return $categories;
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, array{code: string, path: string}>
     */
    private function owners(array $ids): array
    {
        $owners = [];

        if ($ids === []) {
            return $owners;
        }

        foreach ($this->db->table(Tables::name('owners'))->whereIn('id', array_values(array_unique($ids)))->get(['id', 'code', 'path']) as $row) {
            /** @var stdClass $row */
            $owners[(string) $row->id] = ['code' => (string) $row->code, 'path' => (string) $row->path];
        }

        return $owners;
    }

    /**
     * Linked assets by product or model id, in role then display order.
     *
     * @param  list<string>  $productIds
     * @param  list<string>  $modelIds
     * @return array<string, list<array<string, mixed>>>
     */
    private function assets(array $productIds, array $modelIds): array
    {
        $productMorph = (new ProductModel)->getMorphClass();
        $modelMorph = (new ProductModelModel)->getMorphClass();

        $rows = $this->db->table(Tables::name('asset_links').' as l')
            ->join(Tables::name('assets').' as a', 'a.id', '=', 'l.asset_id')
            ->where(function (Builder $query) use ($productMorph, $modelMorph, $productIds, $modelIds): void {
                $query->where(fn (Builder $q) => $q->where('l.linkable_type', $productMorph)->whereIn('l.linkable_id', $productIds));

                if ($modelIds !== []) {
                    $query->orWhere(fn (Builder $q) => $q->where('l.linkable_type', $modelMorph)->whereIn('l.linkable_id', $modelIds));
                }
            })
            ->orderBy('l.role')
            ->orderBy('l.sort_order')
            ->orderBy('a.code')
            ->get(['l.linkable_id', 'l.role', 'l.sort_order', 'a.code', 'a.filename', 'a.mime_type', 'a.size', 'a.checksum', 'a.disk', 'a.path']);

        $assets = [];

        foreach ($rows as $row) {
            /** @var stdClass $row */
            $assets[(string) $row->linkable_id][] = [
                'code' => (string) $row->code,
                'role' => (string) $row->role,
                'sort_order' => (int) $row->sort_order,
                'filename' => (string) $row->filename,
                'mime_type' => is_string($row->mime_type) ? $row->mime_type : null,
                'size' => (int) $row->size,
                'checksum' => is_string($row->checksum) ? $row->checksum : null,
                'disk' => (string) $row->disk,
                'path' => (string) $row->path,
            ];
        }

        return $assets;
    }

    /**
     * Inherited values overlaid with the product's own, in storage form.
     *
     * @param  array<string, mixed>  $published
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function values(array $published): array
    {
        $values = [];

        foreach ([$published['inherited_values'] ?? [], $published['values'] ?? []] as $standard) {
            foreach (is_array($standard) ? $standard : [] as $code => $slots) {
                foreach (is_array($slots) ? $slots : [] as $slot) {
                    if (! is_array($slot)) {
                        continue;
                    }

                    $scope = is_string($slot['scope'] ?? null) ? $slot['scope'] : Values::ALL_CHANNELS;
                    $locale = is_string($slot['locale'] ?? null) ? $slot['locale'] : Values::ALL_LOCALES;

                    $values[(string) $code][$scope][$locale] = $slot['data'] ?? null;
                }
            }
        }

        ksort($values);

        return $values;
    }
}
