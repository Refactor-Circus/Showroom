<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Association\Data\UpdateAssociationTypeData;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;

final class UpdateAssociationTypeAction
{
    public function execute(AssociationTypeModel $associationType, UpdateAssociationTypeData $data): AssociationTypeModel
    {
        AssociationTypeUpdatingActionEvent::dispatch($associationType, $data);

        $result = $this->perform($associationType, $data);

        AssociationTypeUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AssociationTypeModel $associationType, UpdateAssociationTypeData $data): AssociationTypeModel
    {
        return DB::transaction(function () use ($associationType, $data): AssociationTypeModel {
            if ($data->has('labels')) {
                $associationType->labels = is_array($data->labels) ? $data->labels : [];
                $associationType->save();
            }

            return $associationType;
        });
    }
}
