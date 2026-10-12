<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Links between assets and the records they illustrate.
 *
 * Links are polymorphic, so no foreign key removes them when a record goes;
 * the Actions that delete records call forget() instead.
 */
final class AssetLinks
{
    /**
     * The kinds of record an asset can be linked to, as the API names them.
     *
     * @var array<string, class-string<ProductModel|ProductModelModel|OwnerModel>>
     */
    public const array TYPES = [
        'product' => ProductModel::class,
        'product_model' => ProductModelModel::class,
        'owner' => OwnerModel::class,
    ];

    /**
     * The morph aliases links are stored under.
     *
     * @var array<string, class-string<Model>>
     */
    public const array MORPHS = [
        'showroom_product' => ProductModel::class,
        'showroom_product_model' => ProductModelModel::class,
        'showroom_owner' => OwnerModel::class,
    ];

    /**
     * Find the record a link points at: a product by identifier, anything
     * else by code.
     *
     * @throws ValidationException
     */
    public function resolve(string $type, string $target): ProductModel|ProductModelModel|OwnerModel
    {
        $record = match ($type) {
            'product' => ProductModel::query()->where('identifier', $target)->first(),
            'product_model' => ProductModelModel::query()->where('code', $target)->first(),
            'owner' => OwnerModel::query()->where('code', $target)->first(),
            default => null,
        };

        if ($record === null) {
            throw ValidationException::withMessages([
                'target' => sprintf('No %s "%s" exists.', str_replace('_', ' ', $type), $target),
            ]);
        }

        return $record;
    }

    public function attach(AssetModel $asset, Model $record, string $role, int $sortOrder): void
    {
        DB::table(Tables::name('asset_links'))->updateOrInsert(
            [
                'asset_id' => $asset->id,
                'linkable_type' => $record->getMorphClass(),
                'linkable_id' => $record->getKey(),
                'role' => $role,
            ],
            ['sort_order' => $sortOrder],
        );
    }

    /**
     * Remove the asset's link to a record, in one role or in all of them.
     */
    public function detach(AssetModel $asset, Model $record, ?string $role): int
    {
        return DB::table(Tables::name('asset_links'))
            ->where('asset_id', $asset->id)
            ->where('linkable_type', $record->getMorphClass())
            ->where('linkable_id', $record->getKey())
            ->when($role !== null, fn (Builder $query): Builder => $query->where('role', $role))
            ->delete();
    }

    /**
     * Remove every link to records of one kind.
     *
     * @param  class-string<Model>  $class
     * @param  array<int, string>  $ids
     */
    public function forget(string $class, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        DB::table(Tables::name('asset_links'))
            ->where('linkable_type', (new $class)->getMorphClass())
            ->whereIn('linkable_id', $ids)
            ->delete();
    }
}
