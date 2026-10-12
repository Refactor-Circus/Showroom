<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class UpdateProductRequest extends ProductRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->product());
    }

    public function rules(): array
    {
        return UpdateProductData::rules();
    }

    public function persist(): JsonResponse
    {
        $product = app(UpdateProductAction::class)->execute($this->product(), UpdateProductData::fromValidated($this->validated()));

        return (new ProductResource($product))->response();
    }
}
