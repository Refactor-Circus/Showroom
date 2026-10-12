<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\AssetFactory;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetStorage;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A file on the configured disk — an image, a manual, a logo — that can be
 * linked to products, product models and owners under a role.
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property string $disk
 * @property string $path
 * @property string $filename
 * @property string|null $mime_type
 * @property int $size
 * @property string|null $checksum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AssetModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('assets');
    }

    protected $fillable = [
        'code',
        'labels',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'checksum',
    ];

    /**
     * @return MorphToMany<ProductModel, $this>
     */
    public function products(): MorphToMany
    {
        return $this->linked(ProductModel::class);
    }

    /**
     * @return MorphToMany<ProductModelModel, $this>
     */
    public function productModels(): MorphToMany
    {
        return $this->linked(ProductModelModel::class);
    }

    /**
     * @return MorphToMany<OwnerModel, $this>
     */
    public function owners(): MorphToMany
    {
        return $this->linked(OwnerModel::class);
    }

    public function url(): string
    {
        return app(AssetStorage::class)->url($this);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    /**
     * @template TLinked of Model
     *
     * @param  class-string<TLinked>  $class
     * @return MorphToMany<TLinked, $this>
     */
    private function linked(string $class): MorphToMany
    {
        return $this->morphedByMany($class, 'linkable', Tables::name('asset_links'), 'asset_id')->withPivot(['role', 'sort_order']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'size' => 'integer',
        ];
    }

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }
}
