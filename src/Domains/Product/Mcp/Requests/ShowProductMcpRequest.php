<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Product\Actions\ShowProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\ShowProductData;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class ShowProductMcpRequest extends ProductRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->product());
    }

    protected function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:191'],
        ] + ShowProductData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $product = app(ShowProductAction::class)->execute($this->product(), ShowProductData::fromValidated($validated));

        return (new ProductResource($product))->toStructuredResponse();
    }
}
