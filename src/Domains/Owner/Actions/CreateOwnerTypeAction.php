<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Owner\Concerns\WritesOwnerTypes;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

final class CreateOwnerTypeAction
{
    use WritesOwnerTypes;

    public function execute(CreateOwnerTypeData $data): OwnerTypeModel
    {
        OwnerTypeCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        OwnerTypeCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateOwnerTypeData $data): OwnerTypeModel
    {
        return DB::transaction(function () use ($data): OwnerTypeModel {
            $type = OwnerTypeModel::query()->create([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'can_be_root' => $data->canBeRoot,
                'owns_products' => $data->ownsProducts,
                'sort_order' => $data->sortOrder,
            ]);

            $this->setParentTypes($type, $data->parentTypes);

            return $type->load('parentTypes');
        });
    }
}
