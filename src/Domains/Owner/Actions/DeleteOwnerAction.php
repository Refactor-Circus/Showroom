<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Exceptions\ModelInUseException;

final class DeleteOwnerAction
{
    public function execute(OwnerModel $owner): OwnerModel
    {
        OwnerDeletingActionEvent::dispatch($owner);

        $result = $this->perform($owner);

        OwnerDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Refused while anything hangs off the owner: silently orphaning products
     * or re-rooting child owners would rewrite the catalog behind the caller.
     */
    private function perform(OwnerModel $owner): OwnerModel
    {
        return DB::transaction(function () use ($owner): OwnerModel {
            $children = $owner->children()->count();
            $products = $owner->products()->count() + $owner->productModels()->count();

            if ($children > 0 || $products > 0) {
                throw ModelInUseException::ownerHasDependents($owner->code, $children, $products);
            }

            $owner->delete();

            app(AssetLinks::class)->forget(OwnerModel::class, [$owner->id]);

            return $owner;
        });
    }
}
