<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\LocaleFactory;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * A language the catalog holds content in: `en`, `fr_CA`. Localizable values
 * can only be written in a locale that exists.
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class LocaleModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<LocaleFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('locales');
    }

    protected $fillable = [
        'code',
        'labels',
    ];

    /**
     * @return BelongsToMany<ChannelModel, $this>
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(ChannelModel::class, Tables::name('channel_locale'), 'locale_id', 'channel_id')->orderBy('code');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
        ];
    }

    protected static function newFactory(): LocaleFactory
    {
        return LocaleFactory::new();
    }
}
