<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Association\Services\Associations;
use RefactorCircus\Showroom\Domains\Attribute\Services\UniqueValues;
use RefactorCircus\Showroom\Domains\Category\Concerns\AssignsCategories;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Concerns\AssignsOwners;
use RefactorCircus\Showroom\Domains\Product\Concerns\WritesProducts;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Events\ProductUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;

final class UpdateProductAction
{
    use AssignsCategories;
    use AssignsOwners;
    use WritesProducts;

    public function __construct(
        private readonly UniqueValues $unique,
        private readonly ProductIndex $index,
        private readonly ExternalIds $externalIds,
        private readonly Flags $flags,
    ) {}

    /**
     * @throws ValidationException
     * @throws AuthorizationException When the flags change and the caller may not assign them.
     */
    public function execute(ProductModel $product, UpdateProductData $data): ProductModel
    {
        ProductUpdatingActionEvent::dispatch($product, $data);

        $result = $this->perform($product, $data);

        ProductUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ProductModel $product, UpdateProductData $data): ProductModel
    {
        return DB::transaction(function () use ($product, $data): ProductModel {
            // Changing the flags needs `assign`, asked before anything is written;
            // the same set sent back needs nothing.
            $flagsChange = is_array($data->flags) && $this->flags->changes($product, $data->flags);

            if ($flagsChange) {
                $this->flags->authorizeAssign($product);
            }

            $parent = $product->parent()->with('familyVariant.family.familyAttributes')->first();

            if (! $data->family instanceof Missing) {
                if ($parent !== null) {
                    throw ValidationException::withMessages([
                        'family' => 'A variant product\'s family comes from its family variant and cannot be changed.',
                    ]);
                }

                // Values outside the new family stay stored, as in any PIM,
                // and reappear if the product moves back.
                $product->family()->associate($data->family !== null
                    ? FamilyModel::query()->where('code', $data->family)->firstOrFail()
                    : null);
            }

            if (! $data->owner instanceof Missing) {
                if ($parent !== null) {
                    $this->refuseInheritedOwner('variant product');
                }

                $product->owner()->associate($this->ownerFor($data->owner));
            }

            if (! $data->enabled instanceof Missing) {
                $product->enabled = $data->enabled;
            }

            if (! $data->values instanceof Missing) {
                [$settable, $why] = $this->settable($product->family, $parent);

                $values = $this->patchValues($product->ownValues(), $data->values, $settable, $why);

                if ($parent !== null) {
                    $this->checkVariantAxes($parent, $values, $product);
                }

                $product->values = $values;
            }

            $product->save();

            // A (source, id) another product holds throws here, rolling back every change.
            if (! $data->externalIds instanceof Missing) {
                $this->externalIds->write($product, $data->externalIds);
            }

            if ($flagsChange) {
                $this->flags->write($product, $data->flags);
            }

            $this->assignCategories($product, $data->toArray());
            app(Associations::class)->write($product, $data->toArray());
            $this->unique->sync($product);

            // An approved product that changes needs review again; the
            // published version is untouched until the next publish.
            $versions = app(Versions::class);

            // One snapshot serves both the check and the version.
            $snapshot = $versions->snapshot($product);

            if ($product->status === ProductStatus::Approved && $versions->hasChanges($product, $snapshot)) {
                $product->status = ProductStatus::Draft;
                $product->save();

                $snapshot['status'] = $product->status->value;
            }

            $versions->record($product, 'updated', snapshot: $snapshot);
            $this->index->queue([$product->id]);

            $product->load(['family', 'owner', 'categories', 'parent.parent', 'completeness.channel', 'completeness.locale', 'externalIds', ...Associations::eagerLoads()]);
            $this->flags->load(new Collection([$product]));

            return $product;
        });
    }
}
