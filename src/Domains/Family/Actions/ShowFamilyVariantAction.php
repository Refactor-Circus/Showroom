<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantShowingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantShownActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

final class ShowFamilyVariantAction
{
    public function execute(FamilyVariantModel $familyVariant): FamilyVariantModel
    {
        FamilyVariantShowingActionEvent::dispatch($familyVariant);

        $result = $this->perform($familyVariant);

        FamilyVariantShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(FamilyVariantModel $familyVariant): FamilyVariantModel
    {
        return $familyVariant->load(['family.familyAttributes', 'variantAttributes'])->loadCount('productModels');
    }
}
