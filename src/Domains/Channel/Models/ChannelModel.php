<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\ChannelFactory;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Somewhere products are published — a storefront, a print catalog, a
 * marketplace — with the locales and currencies it uses and the category
 * tree it sells from. Scopable values hold one value per channel.
 *
 * @property string $id
 * @property string $code
 * @property array<string, string>|null $labels
 * @property array<int, string>|null $currencies
 * @property string|null $category_tree_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CategoryModel|null $categoryTree
 */
final class ChannelModel extends Model
{
    use DispatchesModelEvents;

    /** @use HasFactory<ChannelFactory> */
    use HasFactory;

    use HasLabels;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('channels');
    }

    protected $fillable = [
        'code',
        'labels',
        'currencies',
        'category_tree_id',
    ];

    /**
     * @return BelongsToMany<LocaleModel, $this>
     */
    public function locales(): BelongsToMany
    {
        return $this->belongsToMany(LocaleModel::class, Tables::name('channel_locale'), 'channel_id', 'locale_id')->orderBy('code');
    }

    /**
     * @return BelongsTo<CategoryModel, $this>
     */
    public function categoryTree(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_tree_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'currencies' => 'array',
        ];
    }

    protected static function newFactory(): ChannelFactory
    {
        return ChannelFactory::new();
    }
}
