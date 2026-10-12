<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * One product or product model related to another, under a type — with a
 * quantity when the type is quantified.
 *
 * @property string $id
 * @property string $association_type_id
 * @property string $source_type
 * @property string $source_id
 * @property string $target_type
 * @property string $target_id
 * @property int|null $quantity
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AssociationTypeModel $type
 * @property-read ProductModel|ProductModelModel|null $source
 * @property-read ProductModel|ProductModelModel|null $target
 */
final class AssociationModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('associations');
    }

    protected $fillable = [
        'association_type_id',
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'quantity',
        'sort_order',
    ];

    /**
     * @return BelongsTo<AssociationTypeModel, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssociationTypeModel::class, 'association_type_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
