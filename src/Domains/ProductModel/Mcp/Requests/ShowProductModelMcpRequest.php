<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\ShowProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ShowProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

final class ShowProductModelMcpRequest extends ProductModelRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->productModel());
    }

    protected function rules(): array
    {
        return [
            'product_model' => ['required', 'string', 'max:191'],
        ] + ShowProductModelData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $productModel = app(ShowProductModelAction::class)->execute($this->productModel(), ShowProductModelData::fromValidated($validated));

        return (new ProductModelResource($productModel))->toStructuredResponse();
    }
}
