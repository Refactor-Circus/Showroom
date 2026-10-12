<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Jobs\PurgeAttributeValues;

final class DeleteAttributeOptionAction
{
    public function execute(AttributeOptionModel $option): AttributeOptionModel
    {
        AttributeOptionDeletingActionEvent::dispatch($option);

        $result = $this->perform($option);

        AttributeOptionDeletedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AttributeOptionModel $option): AttributeOptionModel
    {
        return DB::transaction(function () use ($option): AttributeOptionModel {
            $option->delete();

            PurgeAttributeValues::dispatch($option->attribute()->value('code') ?? '', $option->code)->afterCommit();

            return $option;
        });
    }
}
