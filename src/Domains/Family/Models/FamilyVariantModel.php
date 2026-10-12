<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\FamilyVariantFactory;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * How a family's products vary: "shirts by color, then by size". Each level
 * names its axes and the attributes set at that level; the family's other
 * attributes are common, set once on the root product model.
 *
 * @property string $id
 * @property string $family_id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property int $levels
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FamilyModel $family
 */
final class FamilyVariantModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<FamilyVariantFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('family_variants');
    }

    protected $fillable = [
        'family_id',
        'code',
        'labels',
        'levels',
    ];

    /**
     * @return BelongsTo<FamilyModel, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(FamilyModel::class);
    }

    /**
     * Every attribute placed on a level, with `pivot->level` and `pivot->is_axis`.
     *
     * @return BelongsToMany<AttributeModel, $this, Pivot, 'pivot'>
     */
    public function variantAttributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeModel::class, Tables::name('family_variant_attributes'), 'family_variant_id', 'attribute_id')
            ->withPivot(['level', 'is_axis'])
            ->orderByPivot('level')
            ->orderBy(Tables::name('attributes').'.code');
    }

    /**
     * @return HasMany<ProductModelModel, $this>
     */
    public function productModels(): HasMany
    {
        return $this->hasMany(ProductModelModel::class, 'family_variant_id');
    }

    /**
     * The attributes set at a level: 0 is the root model (the common
     * attributes), 1 and 2 are variant levels.
     *
     * @return Collection<int, AttributeModel>
     */
    public function attributesAt(int $level): Collection
    {
        $placed = $this->variantAttributes;

        if ($level > 0) {
            return $placed->filter(fn (AttributeModel $attribute): bool => $this->pivotOf($attribute)['level'] === $level)->values();
        }

        $ids = $placed->modelKeys();

        return $this->family->familyAttributes
            ->reject(fn (AttributeModel $attribute): bool => in_array($attribute->getKey(), $ids, true))
            ->values();
    }

    /**
     * The axes of a variant level.
     *
     * @return Collection<int, AttributeModel>
     */
    public function axesAt(int $level): Collection
    {
        return $this->attributesAt($level)
            ->filter(fn (AttributeModel $attribute): bool => $this->pivotOf($attribute)['is_axis'])
            ->values();
    }

    /**
     * @return array{level: int, is_axis: bool}
     */
    public function pivotOf(AttributeModel $attribute): array
    {
        /** @var Pivot|null $pivot */
        $pivot = $attribute->getRelation('pivot');

        return [
            'level' => (int) $pivot?->getAttribute('level'),
            'is_axis' => (bool) $pivot?->getAttribute('is_axis'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'levels' => 'integer',
        ];
    }

    protected static function newFactory(): FamilyVariantFactory
    {
        return FamilyVariantFactory::new();
    }
}
