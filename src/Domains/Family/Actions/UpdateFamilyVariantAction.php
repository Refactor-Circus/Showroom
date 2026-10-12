<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Family\Concerns\WritesFamilyVariantLevels;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyVariantData;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyVariantUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

final class UpdateFamilyVariantAction
{
    use WritesFamilyVariantLevels;

    /**
     * @throws ValidationException
     */
    public function execute(FamilyVariantModel $familyVariant, UpdateFamilyVariantData $data): FamilyVariantModel
    {
        FamilyVariantUpdatingActionEvent::dispatch($familyVariant, $data);

        $result = $this->perform($familyVariant, $data);

        FamilyVariantUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(FamilyVariantModel $familyVariant, UpdateFamilyVariantData $data): FamilyVariantModel
    {
        return DB::transaction(function () use ($familyVariant, $data): FamilyVariantModel {
            if ($data->has('labels')) {
                $familyVariant->labels = is_array($data->labels) ? $data->labels : [];
                $familyVariant->save();
            }

            if (! $data->levels instanceof Missing) {
                // Stored values sit on the levels they were set at; moving an
                // attribute would strand them.
                if ($familyVariant->productModels()->exists()) {
                    throw ValidationException::withMessages([
                        'levels' => sprintf('Family variant "%s" has product models, so its levels cannot change.', $familyVariant->code),
                    ]);
                }

                $this->placeLevels($familyVariant, $data->levels);
            }

            return $familyVariant->load(['family.familyAttributes', 'variantAttributes']);
        });
    }
}
