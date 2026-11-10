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
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Support\Slugs;

final class CreateOwnerAction
{
    use PlacesOwners;

    public function __construct(
        private readonly Slugs $slugs,
        private readonly ExternalIds $externalIds,
        private readonly Flags $flags,
    ) {}

    /**
     * @throws ValidationException
     * @throws AuthorizationException When flags are given and the caller may not assign them.
     */
    public function execute(CreateOwnerData $data): OwnerModel
    {
        OwnerCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        OwnerCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateOwnerData $data): OwnerModel
    {
        return DB::transaction(function () use ($data): OwnerModel {
            $type = OwnerTypeModel::query()->with('parentTypes')->where('code', $data->type)->firstOrFail();
            $parent = $data->parent !== null ? OwnerModel::query()->where('code', $data->parent)->firstOrFail() : null;

            $owner = new OwnerModel([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'path' => '',
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

            // A (system, id) another owner holds throws here, rolling back the owner.
            $this->externalIds->write($owner, $data->externalIds ?? []);
            $this->flags->write($owner, $flags);

            $owner->load(['type', 'parent', 'externalIds']);
            $this->flags->load(new Collection([$owner]));

            return $owner;
        });
    }
}
