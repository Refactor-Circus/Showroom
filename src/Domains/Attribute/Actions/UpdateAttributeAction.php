<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

final class UpdateAttributeAction
{
    /**
     * @throws ValidationException
     */
    public function execute(AttributeModel $attribute, UpdateAttributeData $data): AttributeModel
    {
        AttributeUpdatingActionEvent::dispatch($attribute, $data);

        $result = $this->perform($attribute, $data);

        AttributeUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AttributeModel $attribute, UpdateAttributeData $data): AttributeModel
    {
        return DB::transaction(function () use ($attribute, $data): AttributeModel {
            if (! $data->isUnique instanceof Missing) {
                $unique = $data->isUnique;

                if ($unique && ! $attribute->type->canBeUnique()) {
                    throw ValidationException::withMessages([
                        'is_unique' => sprintf('A %s attribute cannot be unique.', $attribute->type->value),
                    ]);
                }

                $attribute->is_unique = $unique;
            }

            if (! $data->group instanceof Missing) {
                $attribute->attribute_group_id = $data->group !== null
                    ? AttributeGroupModel::query()->where('code', $data->group)->value('id')
                    : null;
            }

            if (! $data->labels instanceof Missing) {
                $attribute->labels = $data->labels ?? [];
            }

            if (! $data->isLocalizable instanceof Missing) {
                $attribute->is_localizable = $data->isLocalizable;
            }

            if (! $data->isScopable instanceof Missing) {
                $attribute->is_scopable = $data->isScopable;
            }

            // Settings are replaced whole, so a caller never has to know which
            // keys were set before to get a predictable result.
            if (! $data->settings instanceof Missing) {
                $attribute->settings = $attribute->type->validateSettings($data->settings ?? []);
            }

            if (! $data->sortOrder instanceof Missing) {
                $attribute->sort_order = $data->sortOrder;
            }

            if ($attribute->is_unique && ($attribute->is_localizable || $attribute->is_scopable)) {
                throw ValidationException::withMessages([
                    'is_unique' => 'A unique attribute cannot be localizable or scopable: it holds one value per product.',
                ]);
            }

            $attribute->save();

            return $attribute->load('group');
        });
    }
}
