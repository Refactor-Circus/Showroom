<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use RefactorCircus\Showroom\Domains\Family\Events\FamilyShowingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyShownActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

final class ShowFamilyAction
{
    public function execute(FamilyModel $family): FamilyModel
    {
        FamilyShowingActionEvent::dispatch($family);

        $result = $this->perform($family);

        FamilyShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(FamilyModel $family): FamilyModel
    {
        return $family->load(['labelAttribute', 'familyAttributes.group']);
    }
}
