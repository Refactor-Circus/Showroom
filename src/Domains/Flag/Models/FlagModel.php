<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\FlagFactory;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A commercial marker — restricted, pricing only, clearance — assigned to
 * products and owners. An owner's flags carry down to everything beneath it.
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property list<string> $applies_to
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class FlagModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<FlagFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('flags');
    }

    protected $fillable = [
        'code',
        'labels',
        'applies_to',
        'sort_order',
    ];

    /**
     * Whether the flag may be assigned to this kind of record.
     */
    public function appliesTo(FlagSubject $subject): bool
    {
        return in_array($subject->value, $this->applies_to, true);
    }

    /**
     * The flag's assignment rows, optionally of one kind of record.
     */
    public function assignments(?FlagSubject $subject = null): Builder
    {
        return $this->getConnection()
            ->table(Tables::name('flag_assignments'))
            ->where('flag_id', $this->id)
            ->when($subject !== null, fn (Builder $query): Builder => $query->where('subject_type', $subject?->morphClass()));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'applies_to' => 'array',
            'sort_order' => 'integer',
        ];
    }

    protected static function newFactory(): FlagFactory
    {
        return FlagFactory::new();
    }
}
