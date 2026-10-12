<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Family\Concerns\WritesFamilyAttributes;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Family\Events\FamilyUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;

final class UpdateFamilyAction
{
    use WritesFamilyAttributes;

    public function __construct(private readonly ProductIndex $index) {}

    /**
     * @throws ValidationException
     */
    public function execute(FamilyModel $family, UpdateFamilyData $data): FamilyModel
    {
        FamilyUpdatingActionEvent::dispatch($family, $data);

        $result = $this->perform($family, $data);

        FamilyUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(FamilyModel $family, UpdateFamilyData $data): FamilyModel
    {
        return DB::transaction(function () use ($family, $data): FamilyModel {
            if ($data->has('labels')) {
                $family->labels = is_array($data->labels) ? $data->labels : [];
            }

            if (! $data->sortOrder instanceof Missing) {
                $family->sort_order = $data->sortOrder;
            }

            if (! $data->attributes instanceof Missing) {
                $this->keepVariantAttributes($family, $data->attributes);
                $this->replaceAttributes($family, $data->attributes);
            }

            // The label must still belong to the family after its membership
            // changed, so it is checked again whenever either moves.
            if (! $data->labelAttribute instanceof Missing) {
                $this->assignLabelAttribute($family, $data->labelAttribute);
            } elseif ($data->has('attributes') && $family->label_attribute_id !== null) {
                $this->assignLabelAttribute($family, $family->labelAttribute?->code);
            }

            $family->save();

            // Requirements feed every product's completeness.
            if ($data->has('attributes')) {
                $this->index->queueWithin(SyncScope::Family, $family->id);
            }

            return $family->load(['labelAttribute', 'familyAttributes']);
        });
    }
}
