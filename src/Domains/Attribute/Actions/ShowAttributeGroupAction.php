<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupShowingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupShownActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

final class ShowAttributeGroupAction
{
    public function execute(AttributeGroupModel $group): AttributeGroupModel
    {
        AttributeGroupShowingActionEvent::dispatch($group);

        $result = $this->perform($group);

        AttributeGroupShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AttributeGroupModel $group): AttributeGroupModel
    {
        return $group->load('groupedAttributes')->loadCount('groupedAttributes');
    }
}
