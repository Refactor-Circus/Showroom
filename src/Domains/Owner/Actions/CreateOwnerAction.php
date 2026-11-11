<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Services\OwnerWriter;
use RefactorCircus\Showroom\Support\Slugs;

final class CreateOwnerAction
{
    /**
     * Places the owner and writes its slug, external ids and flags.
     */
    private readonly OwnerWriter $writer;

    public function __construct(
        Slugs $slugs,
        ExternalIds $externalIds,
        private readonly Flags $flags,
    ) {
        $this->writer = new OwnerWriter($slugs, $externalIds, $flags);
    }

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

            $owner = $this->writer->create($data, $type, $parent);

            $owner->load(['type', 'parent', 'externalIds']);
            $this->flags->load(new Collection([$owner]));

            return $owner;
        });
    }
}
