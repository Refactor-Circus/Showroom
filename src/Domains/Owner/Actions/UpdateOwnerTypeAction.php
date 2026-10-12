<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Owner\Concerns\WritesOwnerTypes;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerTypeUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

final class UpdateOwnerTypeAction
{
    use WritesOwnerTypes;

    public function execute(OwnerTypeModel $ownerType, UpdateOwnerTypeData $data): OwnerTypeModel
    {
        OwnerTypeUpdatingActionEvent::dispatch($ownerType, $data);

        $result = $this->perform($ownerType, $data);

        OwnerTypeUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(OwnerTypeModel $ownerType, UpdateOwnerTypeData $data): OwnerTypeModel
    {
        return DB::transaction(function () use ($ownerType, $data): OwnerTypeModel {
            if ($data->has('labels')) {
                $ownerType->labels = is_array($data->labels) ? $data->labels : [];
            }

            if (! $data->canBeRoot instanceof Missing) {
                $ownerType->can_be_root = $data->canBeRoot;
            }

            if (! $data->ownsProducts instanceof Missing) {
                $ownerType->owns_products = $data->ownsProducts;
            }

            if (! $data->sortOrder instanceof Missing) {
                $ownerType->sort_order = $data->sortOrder;
            }

            $ownerType->save();

            if (! $data->parentTypes instanceof Missing) {
                $this->setParentTypes($ownerType, $data->parentTypes);
            }

            return $ownerType->load('parentTypes');
        });
    }
}
