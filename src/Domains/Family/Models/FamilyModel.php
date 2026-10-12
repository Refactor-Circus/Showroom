<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\FamilyFactory;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * An attribute set: which attributes a kind of product has, and which of
 * them are required. "Shoes" has size and color; "Laptops" has memory.
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property string|null $label_attribute_id
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AttributeModel|null $labelAttribute
 */
final class FamilyModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<FamilyFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('families');
    }

    protected $fillable = [
        'code',
        'labels',
        'label_attribute_id',
        'sort_order',
    ];

    /**
     * The family's attributes in display order, each with `pivot->is_required`
     * and `pivot->sort_order`.
     *
     * Not `attributes()`: that name is Eloquent's own property on every model.
     *
     * @return BelongsToMany<AttributeModel, $this, Pivot, 'pivot'>
     */
    public function familyAttributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeModel::class, Tables::name('family_attributes'), 'family_id', 'attribute_id')
            ->withPivot(['is_required', 'sort_order', 'required_channels'])
            ->orderByPivot('sort_order')
            ->orderBy(Tables::name('attributes').'.code');
    }

    /**
     * How the family's products vary, when they do.
     *
     * @return HasMany<FamilyVariantModel, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(FamilyVariantModel::class, 'family_id')->orderBy('code');
    }

    /**
     * @return HasMany<ProductModel, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'family_id');
    }

    /**
     * The text attribute whose value is a product's display label.
     *
     * @return BelongsTo<AttributeModel, $this>
     */
    public function labelAttribute(): BelongsTo
    {
        return $this->belongsTo(AttributeModel::class, 'label_attribute_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): FamilyFactory
    {
        return FamilyFactory::new();
    }
}
