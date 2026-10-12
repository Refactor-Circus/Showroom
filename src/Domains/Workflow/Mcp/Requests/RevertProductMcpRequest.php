<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Product\Mcp\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;
use RefactorCircus\Showroom\Domains\Workflow\Actions\RevertProductAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\RevertProductData;

final class RevertProductMcpRequest extends ProductRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->product());
    }

    protected function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:191'],
        ] + RevertProductData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $product = app(RevertProductAction::class)->execute($this->product(), RevertProductData::fromValidated($validated));

        return (new ProductResource($product))->toStructuredResponse();
    }
}
