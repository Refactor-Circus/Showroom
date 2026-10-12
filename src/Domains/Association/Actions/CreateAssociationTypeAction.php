<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Association\Data\CreateAssociationTypeData;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Association\Events\AssociationTypeCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;

final class CreateAssociationTypeAction
{
    public function execute(CreateAssociationTypeData $data): AssociationTypeModel
    {
        AssociationTypeCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        AssociationTypeCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateAssociationTypeData $data): AssociationTypeModel
    {
        return DB::transaction(function () use ($data): AssociationTypeModel {
            return AssociationTypeModel::query()->create([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'is_two_way' => $data->isTwoWay,
                'is_quantified' => $data->isQuantified,
            ]);
        });
    }
}
