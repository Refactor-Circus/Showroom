<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * The flags a product or owner carries: its own, assigned to it, and its
 * effective ones, which add every flag of the owners above it.
 *
 * Effective flags are not a relation, so Flags::load() keeps them on the
 * record outside its relations: a refresh() reloads the relations it holds,
 * and could not reload these.
 */
trait HasFlags
{
    /**
     * Own plus inherited flags, in flag order, once Flags::load() set them.
     *
     * @var Collection<int, FlagModel>|null
     */
    private ?Collection $resolvedEffectiveFlags = null;

    /**
     * The flags assigned to the record itself, in flag order. The
     * assignments are deleted with the record by its delete Action.
     *
     * @return MorphToMany<FlagModel, $this>
     */
    public function flags(): MorphToMany
    {
        return $this->morphToMany(FlagModel::class, 'subject', Tables::name('flag_assignments'), 'subject_id', 'flag_id')
            ->orderBy(Tables::name('flags').'.sort_order')
            ->orderBy(Tables::name('flags').'.code');
    }

    /**
     * Keep the record's effective flags, as Flags::load() resolved them.
     *
     * @param  Collection<int, FlagModel>  $flags
     */
    public function setEffectiveFlags(Collection $flags): static
    {
        $this->resolvedEffectiveFlags = $flags;

        return $this;
    }

    /**
     * The record's effective flags, or null when Flags::load() has not run.
     *
     * @return Collection<int, FlagModel>|null
     */
    public function loadedEffectiveFlags(): ?Collection
    {
        return $this->resolvedEffectiveFlags;
    }
}
