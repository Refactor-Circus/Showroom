<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\CreateProductModelAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\CreateProductModelData;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

final class CreateProductModelMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', ProductModelModel::class);
    }

    protected function rules(): array
    {
        return CreateProductModelData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $productModel = app(CreateProductModelAction::class)->execute(CreateProductModelData::fromValidated($validated));

        return (new ProductModelResource($productModel))->toStructuredResponse();
    }
}
