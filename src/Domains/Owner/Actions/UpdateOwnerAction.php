<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Services\OwnerWriter;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Support\Slugs;

final class UpdateOwnerAction
{
    /**
     * Moves the owner and writes its labels, enabled, external ids and flags.
     */
    private readonly OwnerWriter $writer;

    public function __construct(
        private readonly ProductIndex $index,
        ExternalIds $externalIds,
        private readonly Flags $flags,
    ) {
        $this->writer = new OwnerWriter(app(Slugs::class), $externalIds, $flags);
    }

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
            // A move, an enabled change or a flag change: one queued job
            // lists the products beneath once this commits, however many
            // of them there are.
            if ($this->writer->update($owner, $data)) {
                $this->index->queueWithin(SyncScope::Owner, $owner->id);
            }

            $owner->load(['type', 'parent', 'externalIds']);
            $this->flags->load(new Collection([$owner]));

            return $owner;
        });
    }
}
