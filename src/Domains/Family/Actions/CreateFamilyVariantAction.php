<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Family\Concerns\WritesFamilyVariantLevels;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyVariantData;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

final class CreateFamilyVariantAction
{
    use WritesFamilyVariantLevels;

    /**
     * @throws ValidationException
     */
    public function execute(CreateFamilyVariantData $data): FamilyVariantModel
    {
        FamilyVariantCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        FamilyVariantCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateFamilyVariantData $data): FamilyVariantModel
    {
        return DB::transaction(function () use ($data): FamilyVariantModel {
            $family = FamilyModel::query()->where('code', $data->family)->firstOrFail();

            $variant = new FamilyVariantModel([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
                'levels' => 1,
            ]);
            $variant->family()->associate($family);
            $variant->save();

            $this->placeLevels($variant, $data->levels);

            return $variant->load(['family.familyAttributes', 'variantAttributes']);
        });
    }
}
