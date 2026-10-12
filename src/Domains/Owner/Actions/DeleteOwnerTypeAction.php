<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Exceptions\ModelInUseException;

final class DeleteOwnerTypeAction
{
    public function execute(OwnerTypeModel $ownerType): OwnerTypeModel
    {
        OwnerTypeDeletingActionEvent::dispatch($ownerType);

        $result = $this->perform($ownerType);

        OwnerTypeDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Parent-type rules naming this type go with it; the foreign keys cascade.
     */
    private function perform(OwnerTypeModel $ownerType): OwnerTypeModel
    {
        return DB::transaction(function () use ($ownerType): OwnerTypeModel {
            $owners = $ownerType->owners()->count();

            if ($owners > 0) {
                throw ModelInUseException::ownerTypeHasOwners($ownerType->code, $owners);
            }

            $ownerType->delete();

            return $ownerType;
        });
    }
}
