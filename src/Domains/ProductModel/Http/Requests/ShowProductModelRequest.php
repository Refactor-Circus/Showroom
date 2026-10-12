<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\ShowProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ShowProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

final class ShowProductModelRequest extends ProductModelRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->productModel());
    }

    public function rules(): array
    {
        return ShowProductModelData::rules();
    }

    public function persist(): JsonResponse
    {
        $productModel = app(ShowProductModelAction::class)->execute($this->productModel(), ShowProductModelData::fromValidated($this->validated()));

        return (new ProductModelResource($productModel))->response();
    }
}
