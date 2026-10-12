<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeShowingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeShownActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

final class ShowAttributeAction
{
    public function execute(AttributeModel $attribute): AttributeModel
    {
        AttributeShowingActionEvent::dispatch($attribute);

        $result = $this->perform($attribute);

        AttributeShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AttributeModel $attribute): AttributeModel
    {
        $attribute->load('group');

        if ($attribute->type->hasOptions()) {
            $attribute->load('options');
        }

        return $attribute;
    }
}
