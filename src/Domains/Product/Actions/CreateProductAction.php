<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Services\UniqueValues;
use RefactorCircus\Showroom\Domains\Category\Concerns\AssignsCategories;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Concerns\AssignsOwners;
use RefactorCircus\Showroom\Domains\Product\Concerns\WritesProducts;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Events\ProductCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;

final class CreateProductAction
{
    use AssignsCategories;
    use AssignsOwners;
    use WritesProducts;

    public function __construct(
        private readonly UniqueValues $unique,
        private readonly ProductIndex $index,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(CreateProductData $data): ProductModel
    {
        ProductCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        ProductCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateProductData $data): ProductModel
    {
        return DB::transaction(function () use ($data): ProductModel {
            $parent = $data->parent !== null
                ? ProductModelModel::query()->with('familyVariant.family.familyAttributes')->where('code', $data->parent)->firstOrFail()
                : null;

            $family = $data->family !== null
                ? FamilyModel::query()->where('code', $data->family)->firstOrFail()
                : null;

            if ($parent !== null) {
                if (! $parent->holdsProducts()) {
                    throw ValidationException::withMessages([
                        'parent' => sprintf('Product model "%s" holds sub-models, not products. Choose one of its sub-models.', $parent->code),
                    ]);
                }

                $variantFamily = $parent->familyVariant->family;

                if ($family !== null && ! $family->is($variantFamily)) {
                    throw ValidationException::withMessages([
                        'family' => sprintf('A variant product belongs to its family variant\'s family, "%s".', $variantFamily->code),
                    ]);
                }

                $family = $variantFamily;

                if ($data->owner !== null) {
                    $this->refuseInheritedOwner('variant product');
                }
            }

            $owner = $parent === null ? $this->ownerFor($data->owner) : null;

            [$settable, $why] = $this->settable($family, $parent);

            $values = $this->patchValues([], $data->values, $settable, $why);

            if ($parent !== null) {
                $this->checkVariantAxes($parent, $values);
            }

            $product = new ProductModel([
                'identifier' => $data->identifier,
                'enabled' => $data->enabled,
                'values' => $values,
            ]);
            $product->family()->associate($family);
            $product->parent()->associate($parent);
            $product->owner()->associate($owner);
            $product->save();

            $this->assignCategories($product, $data->toArray());
            app(Associations::class)->write($product, $data->toArray());
            $this->unique->sync($product);
            app(Versions::class)->record($product, 'created');
            $this->index->queue([$product->id]);

            return $product->load(['family', 'owner', 'categories', 'parent.parent', 'completeness.channel', 'completeness.locale', ...Associations::eagerLoads()]);
        });
    }
}
