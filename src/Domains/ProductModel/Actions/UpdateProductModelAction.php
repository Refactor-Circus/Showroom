<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Concerns\WritesValues;
use RefactorCircus\Showroom\Domains\Category\Concerns\AssignsCategories;
use RefactorCircus\Showroom\Domains\Owner\Concerns\AssignsOwners;
use RefactorCircus\Showroom\Domains\ProductModel\Data\UpdateProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;

final class UpdateProductModelAction
{
    use AssignsCategories;
    use AssignsOwners;
    use WritesValues;

    public function __construct(private readonly ProductIndex $index) {}

    /**
     * @throws ValidationException
     */
    public function execute(ProductModelModel $productModel, UpdateProductModelData $data): ProductModelModel
    {
        ProductModelUpdatingActionEvent::dispatch($productModel, $data);

        $result = $this->perform($productModel, $data);

        ProductModelUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ProductModelModel $productModel, UpdateProductModelData $data): ProductModelModel
    {
        return DB::transaction(function () use ($productModel, $data): ProductModelModel {
            if (! $data->values instanceof Missing) {
                $variant = $productModel->familyVariant()->with('family.familyAttributes')->firstOrFail();
                $level = $productModel->level();

                $values = $this->patchValues(
                    $productModel->ownValues(),
                    $data->values,
                    $variant->attributesAt($level),
                    sprintf('is not set at level %d of family variant "%s".', $level, $variant->code),
                );

                if ($level === 1) {
                    $siblings = ProductModelModel::query()
                        ->where('parent_id', $productModel->parent_id)
                        ->whereKeyNot($productModel->getKey())
                        ->get()
                        ->map(fn (ProductModelModel $sibling): array => $sibling->ownValues());

                    $this->checkAxes($variant, 1, $values, $siblings);
                }

                $productModel->values = $values;
                $productModel->save();

                // Variant products carry the model's values in the index.
                $this->index->queue($this->index->productIdsUnder($productModel));
            }

            if (! $data->owner instanceof Missing) {
                if ($productModel->parent_id !== null) {
                    $this->refuseInheritedOwner('sub-model');
                }

                $productModel->owner()->associate($this->ownerFor($data->owner));
                $productModel->save();

                $this->index->queue($this->index->productIdsUnder($productModel));
            }

            app(Associations::class)->write($productModel, $data->toArray());

            if ($data->has('categories')) {
                $this->assignCategories($productModel, $data->toArray());

                // Variants inherit the model's categories.
                $this->index->queue($this->index->productIdsUnder($productModel));
            }

            return $productModel->load(['familyVariant', 'parent', 'owner', 'categories', ...Associations::eagerLoads()]);
        });
    }
}
