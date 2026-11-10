<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    public function __construct(private readonly Slugs $slugs) {}

    /**
     * @throws ValidationException
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

            // The path holds the owner's own id, which exists once saved.
            $this->slugs->claim('owners', Slugs::ownerSource($owner->labels, $owner->code), function (string $slug) use ($owner): void {
                $owner->slug = $slug;
                $owner->save();
            });
            $this->place($owner, $parent);

            return $owner->load(['type', 'parent']);
        });
    }
}
