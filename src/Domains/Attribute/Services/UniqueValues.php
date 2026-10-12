<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Enforces `is_unique` attributes: each value is mirrored as a hash in
 * the `product_unique_values` table, whose unique index the database checks.
 */
final class UniqueValues
{
    /**
     * Record the product's unique values, refusing any another product holds.
     *
     * @throws ValidationException
     */
    public function sync(ProductModel $product): void
    {
        $attributes = AttributeModel::query()->where('is_unique', true)->get(['id', 'code']);

        foreach ($attributes as $attribute) {
            // Unique attributes are neither localizable nor scopable.
            $data = Values::get($product->ownValues(), $attribute->code);

            $rows = DB::table(Tables::name('product_unique_values'))->where('attribute_id', $attribute->id)->where('product_id', $product->id);

            if ($data === null) {
                $rows->delete();

                continue;
            }

            $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));

            $holder = DB::table(Tables::name('product_unique_values').' as unique_values')
                ->join(Tables::name('products'), Tables::name('products').'.id', '=', 'unique_values.product_id')
                ->where('unique_values.attribute_id', $attribute->id)
                ->where('unique_values.value_hash', $hash)
                ->where('unique_values.product_id', '!=', $product->id)
                ->value(Tables::name('products').'.identifier');

            if ($holder !== null) {
                throw $this->taken($attribute, $data, (string) $holder);
            }

            try {
                DB::table(Tables::name('product_unique_values'))->updateOrInsert(
                    ['attribute_id' => $attribute->id, 'product_id' => $product->id],
                    ['value_hash' => $hash],
                );
            } catch (UniqueConstraintViolationException) {
                // Another write took the value between the check and here.
                throw $this->taken($attribute, $data, 'another product');
            }
        }
    }

    private function taken(AttributeModel $attribute, mixed $data, string $holder): ValidationException
    {
        return ValidationException::withMessages([
            'values.'.$attribute->code => sprintf(
                'The value %s of unique attribute "%s" is already used by %s.',
                json_encode($data, JSON_THROW_ON_ERROR),
                $attribute->code,
                $holder === 'another product' ? $holder : 'product "'.$holder.'"',
            ),
        ]);
    }
}
