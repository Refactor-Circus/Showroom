<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;

final class UpdateAttributeOptionAction
{
    public function execute(AttributeOptionModel $option, UpdateAttributeOptionData $data): AttributeOptionModel
    {
        AttributeOptionUpdatingActionEvent::dispatch($option, $data);

        $result = $this->perform($option, $data);

        AttributeOptionUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AttributeOptionModel $option, UpdateAttributeOptionData $data): AttributeOptionModel
    {
        return DB::transaction(function () use ($option, $data): AttributeOptionModel {
            if (! $data->labels instanceof Missing) {
                $option->labels = $data->labels ?? [];
            }

            if (! $data->sortOrder instanceof Missing) {
                $option->sort_order = $data->sortOrder;
            }

            $option->save();

            return $option;
        });
    }
}
