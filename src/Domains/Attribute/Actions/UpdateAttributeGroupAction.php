<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

final class UpdateAttributeGroupAction
{
    public function execute(AttributeGroupModel $group, UpdateAttributeGroupData $data): AttributeGroupModel
    {
        AttributeGroupUpdatingActionEvent::dispatch($group, $data);

        $result = $this->perform($group, $data);

        AttributeGroupUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AttributeGroupModel $group, UpdateAttributeGroupData $data): AttributeGroupModel
    {
        return DB::transaction(function () use ($group, $data): AttributeGroupModel {
            if (! $data->labels instanceof Missing) {
                $group->labels = $data->labels ?? [];
            }

            if (! $data->sortOrder instanceof Missing) {
                $group->sort_order = $data->sortOrder;
            }

            $group->save();

            return $group;
        });
    }
}
