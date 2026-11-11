<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Services;

use Illuminate\Support\Collection;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

/**
 * Whether a product is effectively enabled: it is enabled, and so is every
 * owner of its chain — its effective owner (a variant's root model's owner)
 * and that owner's ancestors, read from its path. Disabling a vendor
 * switches off every product beneath it, without touching their own
 * `enabled`.
 *
 * The search document (`effective_enabled`) and both engines' filter follow
 * the same rule; DatabaseEngine computes it in SQL.
 */
final class EffectiveEnabled
{
    /**
     * Set each product's effective enabled (ProductModel::setEffectiveEnabled()),
     * reading the owners of every chain in one query for the whole batch, so
     * a resource reads it without more. The products' effective owners must
     * be loaded, as Flags::load() needs them too.
     *
     * @param  Collection<int, ProductModel>  $products
     */
    public static function load(Collection $products): void
    {
        $chains = [];
        $owners = [];

        foreach ($products as $product) {
            $chain = self::chainOf($product->effectiveOwner());
            $chains[$product->id] = $chain;
            $owners += array_fill_keys($chain, true);
        }

        /** @var array<string, bool> $enabled */
        $enabled = $owners === [] ? [] : OwnerModel::query()
            ->whereKey(array_keys($owners))
            ->get(['id', 'enabled'])
            ->mapWithKeys(fn (OwnerModel $owner): array => [$owner->id => $owner->enabled])
            ->all();

        foreach ($products as $product) {
            $product->setEffectiveEnabled(self::resolve($product->enabled, $chains[$product->id], $enabled));
        }
    }

    /**
     * Whether a product is effectively enabled, given its own flag, the ids
     * of its owner chain and whether each owner is enabled. An owner missing
     * from the map has been deleted, and does not switch the product off.
     *
     * @param  list<string>  $chain
     * @param  array<string, bool>  $ownersEnabled  By owner id.
     */
    public static function resolve(bool $enabled, array $chain, array $ownersEnabled): bool
    {
        if (! $enabled) {
            return false;
        }

        foreach ($chain as $id) {
            if (($ownersEnabled[$id] ?? true) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * The ids of an owner's chain, root first; none without an owner.
     *
     * @return list<string>
     */
    public static function chainOf(?OwnerModel $owner): array
    {
        if ($owner === null) {
            return [];
        }

        return array_values(array_filter(explode('/', $owner->path), fn (string $id): bool => $id !== ''));
    }
}
