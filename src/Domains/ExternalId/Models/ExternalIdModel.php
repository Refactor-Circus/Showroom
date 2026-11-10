<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ExternalId\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A product's or an owner's id in another system, its source (an ERP, a
 * shop), so a later sync finds the record it wrote before. One holder per id
 * in a source, per kind of record, and one id per record and source, as
 * Roster's organization links. Written only through the subject's actions,
 * by the ExternalIds service.
 *
 * @property string $id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $source
 * @property string $external_id
 * @property Carbon|null $synced_at When the source last wrote or confirmed the id.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProductModel|OwnerModel|null $subject
 */
final class ExternalIdModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('external_ids');
    }

    protected $fillable = [
        'subject_type',
        'subject_id',
        'source',
        'external_id',
        'synced_at',
    ];

    /**
     * The product or owner holding the id.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
