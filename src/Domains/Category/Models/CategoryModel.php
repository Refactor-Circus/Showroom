<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\CategoryFactory;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Models\Concerns\HasPath;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A node of a category tree. A category without a parent is the root of a
 * tree — "Master catalog", "Web navigation" — and trees are independent.
 *
 * @property string $id
 * @property string|null $parent_id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property int $sort_order
 * @property string $path
 * @property int $depth
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CategoryModel|null $parent
 */
final class CategoryModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasLabels;
    use HasPath;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('categories');
    }

    protected $fillable = [
        'parent_id',
        'code',
        'labels',
        'sort_order',
        'path',
        'depth',
    ];

    /**
     * @return BelongsTo<CategoryModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CategoryModel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    /**
     * @return BelongsToMany<ProductModel, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductModel::class, Tables::name('category_product'), 'category_id', 'product_id');
    }

    /**
     * @return BelongsToMany<ProductModelModel, $this>
     */
    public function productModels(): BelongsToMany
    {
        return $this->belongsToMany(ProductModelModel::class, Tables::name('category_product_model'), 'category_id', 'product_model_id');
    }

    /**
     * The root of this category's tree.
     */
    public function tree(): self
    {
        $root = array_values(array_filter(explode('/', $this->path)))[0] ?? $this->id;

        return $root === $this->id ? $this : self::query()->findOrFail($root);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'sort_order' => 'integer',
            'depth' => 'integer',
        ];
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
