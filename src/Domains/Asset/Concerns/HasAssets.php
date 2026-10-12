<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Assets linked to this record, each under a role, in display order.
 */
trait HasAssets
{
    /**
     * @return MorphToMany<AssetModel, $this>
     */
    public function assets(): MorphToMany
    {
        return $this->morphToMany(AssetModel::class, 'linkable', Tables::name('asset_links'), relatedPivotKey: 'asset_id')
            ->withPivot(['role', 'sort_order'])
            ->orderByPivot('role')
            ->orderByPivot('sort_order');
    }
}
