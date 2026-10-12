<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Actions;

use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeShowingActionEvent;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeShownActionEvent;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;

final class ShowAssociationTypeAction
{
    public function execute(AssociationTypeModel $associationType): AssociationTypeModel
    {
        AssociationTypeShowingActionEvent::dispatch($associationType);

        $result = $this->perform($associationType);

        AssociationTypeShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AssociationTypeModel $associationType): AssociationTypeModel
    {
        return $associationType->loadCount('associations');
    }
}
