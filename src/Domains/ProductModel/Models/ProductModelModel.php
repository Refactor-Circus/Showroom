<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\ProductModelFactory;
use RefactorCircus\Showroom\Domains\Asset\Concerns\HasAssets;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Association\Concerns\HasAssociations;
use RefactorCircus\Showroom\Domains\Attribute\Concerns\HasValues;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * The shared part of a group of variant products: "Classic tee" holds the
 * description every color and size shares. A root model sits at level 0; in
 * a two-level family variant, its sub-models sit at level 1.
 *
 * @property string $id
 * @property string $code
 * @property string $family_variant_id
 * @property string|null $parent_id
 * @property string|null $owner_id
 * @property array<string, array<string, array<string, mixed>>>|null $values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FamilyVariantModel $familyVariant
 * @property-read ProductModelModel|null $parent
 * @property-read OwnerModel|null $owner
 */
final class ProductModelModel extends Model
{
    use DispatchesModelEvents;
    use HasAssets;
    use HasAssociations;

    /** @use HasFactory<ProductModelFactory> */
    use HasFactory;

    use HasUlids;
    use HasValues;

    public function getTable(): string
    {
        return Tables::name('product_models');
    }

    protected $fillable = [
        'code',
        'family_variant_id',
        'parent_id',
        'owner_id',
        'values',
    ];

    /**
     * @return BelongsTo<FamilyVariantModel, $this>
     */
    public function familyVariant(): BelongsTo
    {
        return $this->belongsTo(FamilyVariantModel::class);
    }

    /**
     * @return BelongsTo<ProductModelModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Sub-models, in a two-level family variant.
     *
     * @return HasMany<ProductModelModel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    /**
     * The variant products directly under this model.
     *
     * @return HasMany<ProductModel, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'parent_id')->orderBy('identifier');
    }

    /**
     * The owner of a root model; sub-models take their root's.
     *
     * @return BelongsTo<OwnerModel, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(OwnerModel::class);
    }

    /**
     * The categories assigned to this model itself.
     *
     * @return BelongsToMany<CategoryModel, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryModel::class, Tables::name('category_product_model'), 'product_model_id', 'category_id')->orderBy('code');
    }

    /**
     * Own categories plus the root model's, for a sub-model.
     *
     * @return EloquentCollection<int, CategoryModel>
     */
    public function allCategories(): EloquentCollection
    {
        return $this->categories->merge($this->parent?->allCategories() ?? [])->unique('id')->sortBy('code')->values();
    }

    /**
     * Own assets, then the root model's, for a sub-model.
     *
     * @return EloquentCollection<int, AssetModel>
     */
    public function allAssets(): EloquentCollection
    {
        return $this->assets->concat($this->parent?->allAssets() ?? []);
    }

    public function rootModel(): self
    {
        return $this->parent ?? $this;
    }

    /**
     * 0 for a root model, 1 for a sub-model.
     */
    public function level(): int
    {
        return $this->parent_id === null ? 0 : 1;
    }

    /**
     * Whether variant products hang directly off this model.
     */
    public function holdsProducts(): bool
    {
        return $this->level() === $this->familyVariant->levels - 1;
    }

    /**
     * The attributes set at this model's level.
     *
     * @return EloquentCollection<int, AttributeModel>
     */
    public function settableAttributes(): EloquentCollection
    {
        return $this->familyVariant->attributesAt($this->level());
    }

    public function inheritedValues(): array
    {
        return $this->parent?->allValues() ?? [];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }

    protected static function newFactory(): ProductModelFactory
    {
        return ProductModelFactory::new();
    }
}
