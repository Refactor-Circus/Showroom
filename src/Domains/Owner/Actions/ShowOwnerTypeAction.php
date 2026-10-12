<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeShowingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeShownActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

final class ShowOwnerTypeAction
{
    public function execute(OwnerTypeModel $ownerType): OwnerTypeModel
    {
        OwnerTypeShowingActionEvent::dispatch($ownerType);

        $result = $this->perform($ownerType);

        OwnerTypeShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(OwnerTypeModel $ownerType): OwnerTypeModel
    {
        return $ownerType->load('parentTypes')->loadCount('owners');
    }
}
