<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\AttributeOptionFactory;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * One choice of a select or multiselect attribute. Its code is unique within
 * the attribute and never changes once created.
 *
 * @property string $id
 * @property string $attribute_id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AttributeModel $attribute
 */
final class AttributeOptionModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<AttributeOptionFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('attribute_options');
    }

    protected $fillable = [
        'attribute_id',
        'code',
        'labels',
        'sort_order',
    ];

    /**
     * @return BelongsTo<AttributeModel, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeModel::class);
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

    protected static function newFactory(): AttributeOptionFactory
    {
        return AttributeOptionFactory::new();
    }
}
