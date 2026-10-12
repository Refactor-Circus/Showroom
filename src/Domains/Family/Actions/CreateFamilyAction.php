<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Family\Concerns\WritesFamilyAttributes;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

final class CreateFamilyAction
{
    use WritesFamilyAttributes;

    /**
     * @throws ValidationException
     */
    public function execute(CreateFamilyData $data): FamilyModel
    {
        FamilyCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        FamilyCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateFamilyData $data): FamilyModel
    {
        return DB::transaction(function () use ($data): FamilyModel {
            $family = FamilyModel::query()->create([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'sort_order' => $data->sortOrder,
            ]);

            $this->replaceAttributes($family, $data->attributes ?? []);

            if ($data->labelAttribute !== null) {
                $this->assignLabelAttribute($family, $data->labelAttribute);
                $family->save();
            }

            return $family->load(['labelAttribute', 'familyAttributes']);
        });
    }
}
