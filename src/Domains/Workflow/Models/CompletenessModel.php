<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * How complete a product is for one channel and locale. Derived: rewritten
 * whenever the product, its models, its family or the channel changes.
 *
 * @property string $product_id
 * @property string $channel_id
 * @property string $locale_id
 * @property int $required
 * @property int $missing
 * @property int $ratio
 * @property array<int, string>|null $missing_attributes
 * @property Carbon|null $updated_at
 * @property-read ChannelModel $channel
 * @property-read LocaleModel $locale
 * @property-read ProductModel|null $product
 */
final class CompletenessModel extends Model
{
    use DispatchesModelEvents;

    public const null CREATED_AT = null;

    public $incrementing = false;

    public function getTable(): string
    {
        return Tables::name('product_completeness');
    }

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_id',
        'channel_id',
        'locale_id',
        'required',
        'missing',
        'ratio',
        'missing_attributes',
    ];

    /**
     * @return BelongsTo<ChannelModel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelModel::class);
    }

    /**
     * @return BelongsTo<LocaleModel, $this>
     */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(LocaleModel::class);
    }

    /**
     * @return BelongsTo<ProductModel, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required' => 'integer',
            'missing' => 'integer',
            'ratio' => 'integer',
            'missing_attributes' => 'array',
        ];
    }
}
