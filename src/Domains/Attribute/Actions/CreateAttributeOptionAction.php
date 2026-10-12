<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Exceptions\AttributeHasNoOptionsException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;

final class CreateAttributeOptionAction
{
    /**
     * @throws ValidationException
     */
    public function execute(AttributeModel $attribute, CreateAttributeOptionData $data): AttributeOptionModel
    {
        AttributeOptionCreatingActionEvent::dispatch($attribute, $data);

        $result = $this->perform($attribute, $data);

        AttributeOptionCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AttributeModel $attribute, CreateAttributeOptionData $data): AttributeOptionModel
    {
        return DB::transaction(function () use ($attribute, $data): AttributeOptionModel {
            if (! $attribute->type->hasOptions()) {
                throw AttributeHasNoOptionsException::for($attribute);
            }

            if ($attribute->options()->where('code', $data->code)->exists()) {
                throw ValidationException::withMessages([
                    'code' => sprintf('Attribute "%s" already has an option with this code.', $attribute->code),
                ]);
            }

            /** @var AttributeOptionModel $option */
            $option = $attribute->options()->create([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'sort_order' => $data->sortOrder,
            ]);

            return $option;
        });
    }
}
