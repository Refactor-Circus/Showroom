<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\AttributeGroupFactory;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A named section attributes are organised into, such as "Marketing" or
 * "Technical". Purely organisational: it carries no validation.
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class AttributeGroupModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<AttributeGroupFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('attribute_groups');
    }

    protected $fillable = [
        'code',
        'labels',
        'sort_order',
    ];

    /**
     * Not `attributes()`: that name is Eloquent's own property on every model.
     *
     * @return HasMany<AttributeModel, $this>
     */
    public function groupedAttributes(): HasMany
    {
        return $this->hasMany(AttributeModel::class, 'attribute_group_id')->orderBy('sort_order')->orderBy('code');
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

    protected static function newFactory(): AttributeGroupFactory
    {
        return AttributeGroupFactory::new();
    }
}
