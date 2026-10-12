<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Exceptions\ModelInUseException;

final class DeleteFamilyAction
{
    public function execute(FamilyModel $family): FamilyModel
    {
        FamilyDeletingActionEvent::dispatch($family);

        $result = $this->perform($family);

        FamilyDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Membership rows and unused family variants go with the family; the
     * foreign keys cascade. A family with products is refused.
     */
    private function perform(FamilyModel $family): FamilyModel
    {
        return DB::transaction(function () use ($family): FamilyModel {
            $products = $family->products()->count();
            $models = ProductModelModel::query()
                ->whereHas('familyVariant', fn (Builder $variants): Builder => $variants->where('family_id', $family->id))
                ->count();

            if ($products > 0 || $models > 0) {
                throw ModelInUseException::familyHasProducts($family->code, $products, $models);
            }

            $family->delete();

            return $family;
        });
    }
}
