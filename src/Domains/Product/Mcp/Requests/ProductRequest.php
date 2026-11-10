<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Requests;

use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\RecordLookup;

abstract class ProductRequest extends Request
{
    /**
     * The product the `product` argument names, by the configured lookup key
     * or the call's `by`. An invalid `by` is a validation error, raised
     * before the call is authorized or reaches its action.
     */
    protected function product(): ProductModel
    {
        $product = $this->get('product');

        return app(RecordLookup::class)->product(is_scalar($product) ? (string) $product : '', RecordLookup::by($this->get('by')));
    }
}
