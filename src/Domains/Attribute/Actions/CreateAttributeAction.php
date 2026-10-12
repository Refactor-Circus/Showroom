<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

final class CreateAttributeAction
{
    /**
     * @throws ValidationException
     */
    public function execute(CreateAttributeData $data): AttributeModel
    {
        AttributeCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        AttributeCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateAttributeData $data): AttributeModel
    {
        return DB::transaction(function () use ($data): AttributeModel {
            $type = $data->type;
            $unique = $data->isUnique;

            if ($unique && ! $type->canBeUnique()) {
                throw ValidationException::withMessages([
                    'is_unique' => sprintf('A %s attribute cannot be unique.', $type->value),
                ]);
            }

            if ($unique && ($data->isLocalizable || $data->isScopable)) {
                throw ValidationException::withMessages([
                    'is_unique' => 'A unique attribute cannot be localizable or scopable: it holds one value per product.',
                ]);
            }

            return AttributeModel::query()->create([
                'code' => $data->code,
                'type' => $type,
                'attribute_group_id' => $data->group !== null
                    ? AttributeGroupModel::query()->where('code', $data->group)->value('id')
                    : null,
                'labels' => $data->labels ?? [],
                'is_unique' => $unique,
                'is_localizable' => $data->isLocalizable,
                'is_scopable' => $data->isScopable,
                'settings' => $type->validateSettings($data->settings ?? []),
                'sort_order' => $data->sortOrder,
            ])->load('group');
        });
    }
}
