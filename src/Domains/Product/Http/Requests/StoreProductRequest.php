<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class StoreProductRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', ProductModel::class);
    }

    public function rules(): array
    {
        return CreateProductData::rules();
    }

    public function persist(): JsonResponse
    {
        $product = app(CreateProductAction::class)->execute(CreateProductData::fromValidated($this->validated()));

        return (new ProductResource($product))->response()->setStatusCode(201);
    }
}
