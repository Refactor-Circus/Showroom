<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\OwnerTypeFactory;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A kind of owner — manufacturer, vendor, brand, series — and the rules for
 * where owners of this kind may sit in an ownership chain.
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property bool $restricts_parents
 * @property bool $can_be_root
 * @property bool $owns_products
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class OwnerTypeModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<OwnerTypeFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('owner_types');
    }

    protected $fillable = [
        'code',
        'labels',
        'restricts_parents',
        'can_be_root',
        'owns_products',
        'sort_order',
    ];

    /**
     * The owner types allowed as parent, when `restricts_parents` is set.
     *
     * @return BelongsToMany<OwnerTypeModel, $this, Pivot, 'pivot'>
     */
    public function parentTypes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, Tables::name('owner_type_parents'), 'owner_type_id', 'parent_type_id')
            ->orderBy(Tables::name('owner_types').'.code');
    }

    /**
     * @return HasMany<OwnerModel, $this>
     */
    public function owners(): HasMany
    {
        return $this->hasMany(OwnerModel::class, 'owner_type_id');
    }

    /**
     * Whether an owner of this type may sit under an owner of the given type.
     */
    public function allowsParent(self $type): bool
    {
        return ! $this->restricts_parents || $this->parentTypes->contains('id', $type->id);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'restricts_parents' => 'boolean',
            'can_be_root' => 'boolean',
            'owns_products' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): OwnerTypeFactory
    {
        return OwnerTypeFactory::new();
    }
}
