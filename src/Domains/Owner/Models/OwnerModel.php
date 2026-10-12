<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\OwnerFactory;
use RefactorCircus\Showroom\Domains\Asset\Concerns\HasAssets;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Models\Concerns\HasPath;
use RefactorCircus\Showroom\Support\Tables;

/**
 * One node of an ownership chain: "Acme" the vendor, "Classic" its series.
 * Products and root product models are assigned to the deepest owner.
 *
 * @property string $id
 * @property string $owner_type_id
 * @property string|null $parent_id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property string $path
 * @property int $depth
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OwnerTypeModel $type
 * @property-read OwnerModel|null $parent
 */
final class OwnerModel extends Model
{
    use DispatchesModelEvents;
    use HasAssets;

    /** @use HasFactory<OwnerFactory> */
    use HasFactory;

    use HasLabels;
    use HasPath;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('owners');
    }

    protected $fillable = [
        'owner_type_id',
        'parent_id',
        'code',
        'labels',
        'path',
        'depth',
    ];

    /**
     * @return BelongsTo<OwnerTypeModel, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(OwnerTypeModel::class, 'owner_type_id');
    }

    /**
     * @return BelongsTo<OwnerModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<OwnerModel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    /**
     * @return HasMany<ProductModel, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'owner_id');
    }

    /**
     * @return HasMany<ProductModelModel, $this>
     */
    public function productModels(): HasMany
    {
        return $this->hasMany(ProductModelModel::class, 'owner_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'depth' => 'integer',
        ];
    }

    protected static function newFactory(): OwnerFactory
    {
        return OwnerFactory::new();
    }
}
