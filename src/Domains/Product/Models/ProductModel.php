<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\ProductFactory;
use RefactorCircus\Showroom\Domains\Asset\Concerns\HasAssets;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Association\Concerns\HasAssociations;
use RefactorCircus\Showroom\Domains\Attribute\Concerns\HasValues;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Support\RecordLookup;
use RefactorCircus\Showroom\Support\Slugs;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A sellable item, identified by its identifier (SKU). A simple product has
 * values of its own; a variant product also inherits from its product model.
 *
 * @property string $id
 * @property string $identifier
 * @property string $slug Set once, from the identifier, when the product is created.
 * @property string|null $family_id
 * @property string|null $parent_id
 * @property string|null $owner_id
 * @property bool $enabled
 * @property ProductStatus $status
 * @property int|null $published_version
 * @property Carbon|null $published_at
 * @property array<string, array<string, array<string, mixed>>>|null $values
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $changed_at
 * @property-read FamilyModel|null $family
 * @property-read ProductModelModel|null $parent
 * @property-read OwnerModel|null $owner
 * @property-read EloquentCollection<int, ExternalIdModel> $externalIds
 */
final class ProductModel extends Model
{
    use DispatchesModelEvents;
    use HasAssets;
    use HasAssociations;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasUlids;
    use HasValues;

    public function getTable(): string
    {
        return Tables::name('products');
    }

    /**
     * The key URLs and dashboard links name the product by: its `id`, or its
     * `slug`, from `showroom.lookup.products`. Never its identifier.
     */
    public function getRouteKeyName(): string
    {
        return app(RecordLookup::class)->key('products')->value;
    }

    /**
     * The column defaults, so a new product reads them before it is refreshed.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => true,
        'status' => 'draft',
    ];

    protected $fillable = [
        'identifier',
        'family_id',
        'parent_id',
        'owner_id',
        'enabled',
        'values',
        'status',
        'published_version',
        'published_at',
    ];

    /**
     * @return BelongsTo<FamilyModel, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(FamilyModel::class);
    }

    /**
     * The product model a variant product belongs to.
     *
     * @return BelongsTo<ProductModelModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductModelModel::class, 'parent_id');
    }

    /**
     * The owner of a simple product. Variant products leave this empty and
     * take their root model's owner: see effectiveOwner().
     *
     * @return BelongsTo<OwnerModel, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(OwnerModel::class);
    }

    /**
     * The categories assigned to this product itself.
     *
     * @return BelongsToMany<CategoryModel, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryModel::class, Tables::name('category_product'), 'product_id', 'category_id')->orderBy('code');
    }

    /**
     * Own categories plus every ancestor model's, as a variant inherits them.
     *
     * @return EloquentCollection<int, CategoryModel>
     */
    public function allCategories(): EloquentCollection
    {
        return $this->categories->merge($this->parent?->allCategories() ?? [])->unique('id')->sortBy('code')->values();
    }

    /**
     * Own assets, then every ancestor model's, as a variant inherits them.
     *
     * @return EloquentCollection<int, AssetModel>
     */
    public function allAssets(): EloquentCollection
    {
        return $this->assets->concat($this->parent?->allAssets() ?? []);
    }

    public function effectiveOwner(): ?OwnerModel
    {
        return $this->parent?->rootModel()->owner ?? $this->owner;
    }

    /**
     * The attributes this product may set: a variant's last level, its
     * family's attributes, or any attribute (null) without a family.
     *
     * @return EloquentCollection<int, AttributeModel>|null
     */
    public function settableAttributes(): ?EloquentCollection
    {
        if ($this->parent !== null) {
            $variant = $this->parent->familyVariant;

            return $variant->attributesAt($variant->levels);
        }

        return $this->family?->familyAttributes;
    }

    /**
     * Completeness per channel and locale.
     *
     * @return HasMany<CompletenessModel, $this>
     */
    public function completeness(): HasMany
    {
        return $this->hasMany(CompletenessModel::class, 'product_id');
    }

    /**
     * The product's ids in other systems, by system. Deleted with the
     * product by DeleteProductAction.
     *
     * @return MorphMany<ExternalIdModel, $this>
     */
    public function externalIds(): MorphMany
    {
        return $this->morphMany(ExternalIdModel::class, 'subject')->orderBy('system');
    }

    /**
     * Every recorded version, newest first.
     *
     * @return MorphMany<VersionModel, $this>
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(VersionModel::class, 'versionable')->orderByDesc('version');
    }

    public function isVariant(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Give a new product its ULID and, unless its creator already chose one,
     * its slug from the identifier. This runs on every insert, so a product
     * written outside the actions (a factory, a host's own code) gets a slug
     * too, whether or not model events are faked.
     */
    public function setUniqueIds(): void
    {
        parent::setUniqueIds();

        if ($this->getAttribute('slug') === null) {
            $this->setAttribute('slug', app(Slugs::class)->for('products', (string) $this->getAttribute('identifier')));
        }
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
            'enabled' => 'boolean',
            'status' => ProductStatus::class,
            'published_version' => 'integer',
            'published_at' => 'datetime',
            'changed_at' => 'datetime',
            'values' => 'array',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
