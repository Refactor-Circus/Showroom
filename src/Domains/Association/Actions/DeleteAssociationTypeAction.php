<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Exceptions\ModelInUseException;

final class DeleteAssociationTypeAction
{
    public function execute(AssociationTypeModel $associationType): AssociationTypeModel
    {
        AssociationTypeDeletingActionEvent::dispatch($associationType);

        $result = $this->perform($associationType);

        AssociationTypeDeletedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AssociationTypeModel $associationType): AssociationTypeModel
    {
        return DB::transaction(function () use ($associationType): AssociationTypeModel {
            $count = $associationType->associations()->count();

            if ($count > 0) {
                throw ModelInUseException::associationTypeInUse($associationType->code, $count);
            }

            $associationType->delete();

            return $associationType;
        });
    }
}
