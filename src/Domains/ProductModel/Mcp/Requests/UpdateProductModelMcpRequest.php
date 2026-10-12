<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\UpdateProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\UpdateProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

final class UpdateProductModelMcpRequest extends ProductModelRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->productModel());
    }

    protected function rules(): array
    {
        return [
            'product_model' => ['required', 'string', 'max:191'],
        ] + UpdateProductModelData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $productModel = app(UpdateProductModelAction::class)->execute($this->productModel(), UpdateProductModelData::fromValidated($validated));

        return (new ProductModelResource($productModel))->toStructuredResponse();
    }
}
