<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Concerns\WritesValues;
use RefactorCircus\Showroom\Domains\Category\Concerns\AssignsCategories;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Owner\Concerns\AssignsOwners;
use RefactorCircus\Showroom\Domains\ProductModel\Data\CreateProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelCreatedActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelCreatingActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

final class CreateProductModelAction
{
    use AssignsCategories;
    use AssignsOwners;
    use WritesValues;

    /**
     * @throws ValidationException
     */
    public function execute(CreateProductModelData $data): ProductModelModel
    {
        ProductModelCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        ProductModelCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateProductModelData $data): ProductModelModel
    {
        return DB::transaction(function () use ($data): ProductModelModel {
            $parent = $data->parent !== null
                ? ProductModelModel::query()->with('familyVariant.family.familyAttributes')->where('code', $data->parent)->firstOrFail()
                : null;

            $variant = $parent->familyVariant
                ?? FamilyVariantModel::query()->with('family.familyAttributes')->where('code', $data->familyVariant)->firstOrFail();

            if ($parent !== null) {
                if ($parent->parent_id !== null || $variant->levels < 2) {
                    throw ValidationException::withMessages([
                        'parent' => sprintf('Product model "%s" cannot have sub-models: only the root model of a two-level family variant can.', $parent->code),
                    ]);
                }

                if ($data->familyVariant !== null && $data->familyVariant !== $variant->code) {
                    throw ValidationException::withMessages([
                        'family_variant' => sprintf('A sub-model uses its parent\'s family variant, "%s".', $variant->code),
                    ]);
                }
            }

            if ($parent !== null && $data->owner !== null) {
                $this->refuseInheritedOwner('sub-model');
            }

            $owner = $parent === null ? $this->ownerFor($data->owner) : null;

            $level = $parent === null ? 0 : 1;

            $values = $this->patchValues(
                [],
                $data->values,
                $variant->attributesAt($level),
                sprintf('is not set at level %d of family variant "%s".', $level, $variant->code),
            );

            if ($parent !== null) {
                $this->checkAxes($variant, 1, $values, $parent->children()->get()->map(fn (ProductModelModel $sibling): array => $sibling->ownValues()));
            }

            $model = new ProductModelModel(['code' => $data->code, 'values' => $values]);
            $model->familyVariant()->associate($variant);
            $model->parent()->associate($parent);
            $model->owner()->associate($owner);
            $model->save();

            $this->assignCategories($model, $data->toArray());
            app(Associations::class)->write($model, $data->toArray());

            return $model->load(['familyVariant', 'parent', 'owner', 'categories', ...Associations::eagerLoads()]);
        });
    }
}
