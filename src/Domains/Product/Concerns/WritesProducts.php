<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Concerns;

use Illuminate\Support\Collection;
use RefactorCircus\Showroom\Domains\Attribute\Concerns\WritesValues;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

/**
 * Which attributes a product may set, shared by create and update.
 */
trait WritesProducts
{
    use WritesValues;

    /**
     * A variant sets the last level of its family variant; a simple product,
     * with or without a family, any attribute. The family only sets the
     * completeness standard.
     *
     * @return array{0: Collection<int, AttributeModel>|null, 1: string}
     */
    private function settable(?ProductModelModel $parent): array
    {
        if ($parent !== null) {
            $variant = $parent->familyVariant;

            return [
                $variant->attributesAt($variant->levels),
                sprintf('is not set on variant products of family variant "%s".', $variant->code),
            ];
        }

        return [null, ''];
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $values
     */
    private function checkVariantAxes(ProductModelModel $parent, array $values, ?ProductModel $self = null): void
    {
        $variant = $parent->familyVariant;

        $siblings = ProductModel::query()
            ->where('parent_id', $parent->id)
            ->when($self !== null, fn ($query) => $query->whereKeyNot($self?->getKey()))
            ->get()
            ->map(fn (ProductModel $sibling): array => $sibling->ownValues());

        $this->checkAxes($variant, $variant->levels, $values, $siblings);
    }
}
