<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Exceptions\AttributeGroupNotEmptyException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

final class DeleteAttributeGroupAction
{
    public function execute(AttributeGroupModel $group): AttributeGroupModel
    {
        AttributeGroupDeletingActionEvent::dispatch($group);

        $result = $this->perform($group);

        AttributeGroupDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * A group with attributes is refused rather than emptied: silently
     * ungrouping attributes would reorganise the catalog behind the caller.
     */
    private function perform(AttributeGroupModel $group): AttributeGroupModel
    {
        return DB::transaction(function () use ($group): AttributeGroupModel {
            $count = $group->groupedAttributes()->count();

            if ($count > 0) {
                throw AttributeGroupNotEmptyException::for($group, $count);
            }

            $group->delete();

            return $group;
        });
    }
}
