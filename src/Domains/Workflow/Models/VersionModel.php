<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Support\Tables;

/**
 * One recorded state of a product: what it held, what changed since the
 * version before, who changed it and why.
 *
 * @property string $id
 * @property string $versionable_type
 * @property string $versionable_id
 * @property int $version
 * @property string $action
 * @property array<string, mixed> $snapshot
 * @property array<string, mixed>|null $changes
 * @property string|null $comment
 * @property string|null $author_type
 * @property string|null $author_id
 * @property Carbon|null $created_at
 */
final class VersionModel extends Model
{
    use DispatchesModelEvents;
    use HasUlids;

    public const null UPDATED_AT = null;

    public function getTable(): string
    {
        return Tables::name('versions');
    }

    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version',
        'action',
        'snapshot',
        'changes',
        'comment',
        'author_type',
        'author_id',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function versionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
            'changes' => 'array',
        ];
    }
}
