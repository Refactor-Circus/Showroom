<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    public function __construct(private readonly ProductIndex $index) {}

    /**
     * @throws ValidationException
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

                    // Every product beneath now sits in a different chain;
                    // a queued job lists them once this commits.
                    $this->index->queueWithin(SyncScope::Owner, $owner->id);
                }
            }

            return $owner->load(['type', 'parent']);
        });
    }
}
