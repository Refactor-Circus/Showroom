<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class CreateProductMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', ProductModel::class);
    }

    protected function rules(): array
    {
        return CreateProductData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $product = app(CreateProductAction::class)->execute(CreateProductData::fromValidated($validated));

        return (new ProductResource($product))->toStructuredResponse();
    }
}
