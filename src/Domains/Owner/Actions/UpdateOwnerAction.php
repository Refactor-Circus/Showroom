<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Concerns\PlacesOwners;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;

final class UpdateOwnerAction
{
    use PlacesOwners;

    public function __construct(
        private readonly ProductIndex $index,
        private readonly ExternalIds $externalIds,
        private readonly Flags $flags,
    ) {}

    /**
     * @throws ValidationException
     * @throws AuthorizationException When the flags change and the caller may not assign them.
     */
    public function execute(OwnerModel $owner, UpdateOwnerData $data): OwnerModel
    {
        OwnerUpdatingActionEvent::dispatch($owner, $data);

        $result = $this->perform($owner, $data);

        OwnerUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(OwnerModel $owner, UpdateOwnerData $data): OwnerModel
    {
        return DB::transaction(function () use ($owner, $data): OwnerModel {
            // Changing the flags needs `assign`, asked before anything is written;
            // the same set sent back needs nothing.
            $flagsChange = is_array($data->flags) && $this->flags->changes($owner, $data->flags);

            if ($flagsChange) {
                $this->flags->authorizeAssign($owner);
            }

            // Set when every product beneath shows something new: a move or a flag change.
            $reindex = false;

            if ($data->has('labels')) {
                $owner->labels = is_array($data->labels) ? $data->labels : [];
                $owner->save();
            }

            if ($data->has('parent')) {
                $parent = is_string($data->parent) ? OwnerModel::query()->where('code', $data->parent)->firstOrFail() : null;

                if ($parent?->id !== $owner->parent_id) {
                    $type = $owner->type()->with('parentTypes')->firstOrFail();

                    $this->checkPlacement($owner, $type, $parent);
                    $this->place($owner, $parent);

                    // Every product beneath now sits in a different chain.
                    $reindex = true;
                }
            }

            // A (system, id) another owner holds throws here, rolling back every change.
            if (is_array($data->externalIds)) {
                $this->externalIds->write($owner, $data->externalIds);
            }

            if ($flagsChange) {
                $this->flags->write($owner, $data->flags);

                // Every product beneath inherits the owner's flags.
                $reindex = true;
            }

            // One queued job lists the products beneath once this commits,
            // however many of them there are.
            if ($reindex) {
                $this->index->queueWithin(SyncScope::Owner, $owner->id);
            }

            $owner->load(['type', 'parent', 'externalIds']);
            $this->flags->load(new Collection([$owner]));

            return $owner;
        });
    }
}
