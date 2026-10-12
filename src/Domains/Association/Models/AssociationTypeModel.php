<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\AssociationTypeFactory;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A kind of relation between products: cross-sell, up-sell, accessories,
 * compatible parts (two-way), or a bundle's components (quantified).
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property bool $is_two_way
 * @property bool $is_quantified
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AssociationTypeModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<AssociationTypeFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('association_types');
    }

    protected $fillable = [
        'code',
        'labels',
        'is_two_way',
        'is_quantified',
    ];

    /**
     * @return HasMany<AssociationModel, $this>
     */
    public function associations(): HasMany
    {
        return $this->hasMany(AssociationModel::class, 'association_type_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'is_two_way' => 'boolean',
            'is_quantified' => 'boolean',
        ];
    }

    protected static function newFactory(): AssociationTypeFactory
    {
        return AssociationTypeFactory::new();
    }
}
