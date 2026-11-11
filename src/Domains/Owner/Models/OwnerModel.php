<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Database\Factories\OwnerFactory;
use RefactorCircus\Showroom\Domains\Asset\Concerns\HasAssets;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Flag\Concerns\HasFlags;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Support\Models\Concerns\HasLabels;
use RefactorCircus\Showroom\Support\Models\Concerns\HasPath;
use RefactorCircus\Showroom\Support\RecordLookup;
use RefactorCircus\Showroom\Support\Slugs;
use RefactorCircus\Showroom\Support\Tables;

/**
 * One node of an ownership chain: "Acme" the vendor, "Classic" its series.
 * Products and root product models are assigned to the deepest owner.
 *
 * @property string $id
 * @property string $owner_type_id
 * @property string|null $parent_id
 * @property string $code
 * @property string $slug Set once, from the default-locale label or the code, when the owner is created.
 * @property array<string, string>|null $labels
 * @property string $path
 * @property int $depth
 * @property bool $enabled Its products are effectively enabled only while it and every owner above it are.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OwnerTypeModel $type
 * @property-read OwnerModel|null $parent
 * @property-read Collection<int, ExternalIdModel> $externalIds
 * @property-read Collection<int, FlagModel> $flags
 */
final class OwnerModel extends Model
{
    use DispatchesModelEvents;
    use HasAssets;

    /** @use HasFactory<OwnerFactory> */
    use HasFactory;

    use HasFlags;
    use HasLabels;
    use HasPath;
    use HasUlids;

    public function getTable(): string
    {
        return Tables::name('owners');
    }

    /**
     * The key URLs and dashboard links name the owner by: its `id`, or its
     * `slug`, from `showroom.lookup.owners`. Never its code.
     */
    public function getRouteKeyName(): string
    {
        return app(RecordLookup::class)->key('owners')->value;
    }

    /**
     * The column defaults, so a new owner reads them before it is refreshed.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => true,
    ];

    protected $fillable = [
        'owner_type_id',
        'parent_id',
        'code',
        'labels',
        'path',
        'depth',
        'enabled',
    ];

    /**
     * @return BelongsTo<OwnerTypeModel, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(OwnerTypeModel::class, 'owner_type_id');
    }

    /**
     * @return BelongsTo<OwnerModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<OwnerModel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    /**
     * @return HasMany<ProductModel, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'owner_id');
    }

    /**
     * @return HasMany<ProductModelModel, $this>
     */
    public function productModels(): HasMany
    {
        return $this->hasMany(ProductModelModel::class, 'owner_id');
    }

    /**
     * The owner's ids in other systems, by source. Deleted with the owner by
     * DeleteOwnerAction.
     *
     * @return MorphMany<ExternalIdModel, $this>
     */
    public function externalIds(): MorphMany
    {
        return $this->morphMany(ExternalIdModel::class, 'subject')->orderBy('source');
    }

    /**
     * Give a new owner its ULID and, unless its creator already chose one,
     * its slug (Slugs::ownerSource()). This runs on every insert, so an owner
     * written outside the actions gets a slug too, whether or not model
     * events are faked.
     */
    public function setUniqueIds(): void
    {
        parent::setUniqueIds();

        if ($this->getAttribute('slug') === null) {
            $this->setAttribute('slug', app(Slugs::class)->for('owners', Slugs::ownerSource($this->labels, (string) $this->getAttribute('code'))));
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'depth' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    protected static function newFactory(): OwnerFactory
    {
        return OwnerFactory::new();
    }
}
