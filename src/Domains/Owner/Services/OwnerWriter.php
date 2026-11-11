<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Concerns\PlacesOwners;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Support\Slugs;

/**
 * Writes one owner: where it sits in its chain, its slug, whether it is
 * enabled, its external ids and its own flags. CreateOwnerAction,
 * UpdateOwnerAction and the batch upsert all write through it, so they hold
 * owners to the same rules.
 *
 * The caller resolves the owner's type and parent (a batch loads them once
 * for every record), opens the transaction, fires the events, queues any
 * reindex and loads what it returns: the writer reads neither again and
 * leaves the owner's relations as the write left them.
 *
 * @internal
 */
final class OwnerWriter
{
    use PlacesOwners;

    public function __construct(
        private readonly Slugs $slugs,
        private readonly ExternalIds $externalIds,
        private readonly Flags $flags,
    ) {}

    /**
     * Create the owner the data describes. Call it inside the caller's
     * transaction.
     *
     * @param  OwnerTypeModel  $type  The type the data names, with its `parentTypes`.
     * @param  OwnerModel|null  $parent  The owner the data names as parent, with its `type`; null for a root.
     *
     * @throws ValidationException When the type or parent breaks a placement rule.
     * @throws AuthorizationException When flags are given and the caller may not assign them.
     * @throws InvalidArgumentException When the type or parent is not the one the data names.
     */
    public function create(CreateOwnerData $data, OwnerTypeModel $type, ?OwnerModel $parent): OwnerModel
    {
        if ($type->code !== $data->type) {
            throw new InvalidArgumentException(sprintf('Owner "%s" names type "%s", not "%s".', $data->code, $data->type, $type->code));
        }

        $this->ensureParentNamed($data->code, $data->parent, $parent);

        $owner = new OwnerModel([
            'code' => $data->code,
            'labels' => $data->labels ?? [],
            'path' => '',
            'enabled' => $data->enabled,
        ]);
        $owner->type()->associate($type);

        $this->checkPlacement($owner, $type, $parent);

        $flags = $data->flags ?? [];

        // Giving an owner flags needs `assign`, asked before anything is written.
        if ($this->flags->changes($owner, $flags)) {
            $this->flags->authorizeAssign($owner);
        }

        // The path holds the owner's own id, which exists once saved.
        $this->slugs->claim('owners', Slugs::ownerSource($owner->labels, $owner->code), function (string $slug) use ($owner): void {
            $owner->slug = $slug;
            $owner->save();
        });
        $this->place($owner, $parent);

        // A (source, id) another owner holds throws here, rolling back the owner.
        $this->externalIds->write($owner, $data->externalIds ?? []);
        $this->flags->write($owner, $flags);

        return $owner;
    }

    /**
     * Change what the data sends of an owner: its labels, parent, enabled,
     * external ids and flags. Fields left out are kept; a null parent makes
     * it a root. Call it inside the caller's transaction.
     *
     * @param  OwnerModel|null  $parent  The owner the data names as parent, with its `type`; read here when the data names one and none is given.
     * @param  OwnerTypeModel|null  $type  The owner's type, with its `parentTypes`; read here when a move needs it and none is given.
     * @return bool Whether every product beneath shows something new: the owner moved, was enabled or disabled, or its flags changed.
     *
     * @throws ValidationException When the move breaks a placement rule.
     * @throws AuthorizationException When the flags change and the caller may not assign them.
     * @throws InvalidArgumentException When the parent or type given is not the one the data names or the owner has.
     */
    public function update(OwnerModel $owner, UpdateOwnerData $data, ?OwnerModel $parent = null, ?OwnerTypeModel $type = null): bool
    {
        $named = $data->has('parent') && is_string($data->parent) ? $data->parent : null;

        if ($parent !== null) {
            $this->ensureParentNamed($owner->code, $named, $parent);
        }

        if ($type !== null && (string) $type->getKey() !== (string) $owner->owner_type_id) {
            throw new InvalidArgumentException(sprintf('Owner "%s" is not a "%s".', $owner->code, $type->code));
        }

        // Changing the flags needs `assign`, asked before anything is written;
        // the same set sent back needs nothing.
        $flagsChange = is_array($data->flags) && $this->flags->changes($owner, $data->flags);

        if ($flagsChange) {
            $this->flags->authorizeAssign($owner);
        }

        // Set when every product beneath shows something new: a move, an
        // enabled change or a flag change.
        $reindex = false;

        $enabledChanges = is_bool($data->enabled) && $data->enabled !== $owner->enabled;

        if ($data->has('labels')) {
            $owner->labels = is_array($data->labels) ? $data->labels : [];
        }

        if ($enabledChanges) {
            $owner->enabled = (bool) $data->enabled;

            // Every product beneath is effectively enabled only while its whole chain is.
            $reindex = true;
        }

        if ($data->has('labels') || $enabledChanges) {
            $owner->save();
        }

        if ($data->has('parent')) {
            if ($named !== null) {
                $parent ??= OwnerModel::query()->where('code', $named)->firstOrFail();
            }

            if ($parent?->id !== $owner->parent_id) {
                $type ??= $owner->type()->with('parentTypes')->firstOrFail();

                $this->checkPlacement($owner, $type, $parent);
                $this->place($owner, $parent);

                // Every product beneath now sits in a different chain.
                $reindex = true;
            }
        }

        // A (source, id) another owner holds throws here, rolling back every change.
        if (is_array($data->externalIds)) {
            $this->externalIds->write($owner, $data->externalIds);
        }

        if ($flagsChange) {
            $this->flags->write($owner, $data->flags);

            // Every product beneath inherits the owner's flags.
            $reindex = true;
        }

        return $reindex;
    }

    /**
     * Refuse a parent other than the one the data names: a caller that
     * passed the wrong one would place the owner somewhere it was not sent.
     *
     * @throws InvalidArgumentException
     */
    private function ensureParentNamed(string $code, ?string $named, ?OwnerModel $parent): void
    {
        if ($parent?->code !== $named) {
            throw new InvalidArgumentException(sprintf(
                'Owner "%s" names parent %s, not %s.',
                $code,
                $named === null ? 'none' : '"'.$named.'"',
                $parent === null ? 'none' : '"'.$parent->code.'"',
            ));
        }
    }
}
