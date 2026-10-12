<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

final class CreateAttributeGroupAction
{
    public function execute(CreateAttributeGroupData $data): AttributeGroupModel
    {
        AttributeGroupCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        AttributeGroupCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateAttributeGroupData $data): AttributeGroupModel
    {
        return DB::transaction(function () use ($data): AttributeGroupModel {
            return AttributeGroupModel::query()->create([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'sort_order' => $data->sortOrder,
            ]);
        });
    }
}
