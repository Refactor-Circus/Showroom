<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionsListedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionsListingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Exceptions\AttributeHasNoOptionsException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;

final class ListAttributeOptionsAction
{
    /**
     * @return CursorPaginator<int, AttributeOptionModel>
     */
    public function execute(AttributeModel $attribute, ListAttributeOptionsData $filters = new ListAttributeOptionsData): CursorPaginator
    {
        AttributeOptionsListingActionEvent::dispatch($attribute, $filters);

        $result = $this->perform($attribute, $filters);

        AttributeOptionsListedActionEvent::dispatch($attribute, $result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, AttributeOptionModel>
     */
    private function perform(AttributeModel $attribute, ListAttributeOptionsData $filters): CursorPaginator
    {
        if (! $attribute->type->hasOptions()) {
            throw AttributeHasNoOptionsException::for($attribute);
        }

        $query = $attribute->options()->getQuery();

        if ($filters->search !== null && $filters->search !== '') {
            $query->search($filters->search);
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListAttributeOptionsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
