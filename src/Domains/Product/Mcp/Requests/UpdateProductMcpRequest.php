<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class UpdateProductMcpRequest extends ProductRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->product());
    }

    protected function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:191'],
        ] + UpdateProductData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $product = app(UpdateProductAction::class)->execute($this->product(), UpdateProductData::fromValidated($validated));

        return (new ProductResource($product))->toStructuredResponse();
    }
}
