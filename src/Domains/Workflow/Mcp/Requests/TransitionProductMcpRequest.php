<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Product\Mcp\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;
use RefactorCircus\Showroom\Domains\Workflow\Actions\TransitionProductAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\TransitionProductData;

final class TransitionProductMcpRequest extends ProductRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->product());
    }

    protected function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:191'],
        ] + TransitionProductData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $product = app(TransitionProductAction::class)->execute($this->product(), TransitionProductData::fromValidated($validated));

        return (new ProductResource($product))->toStructuredResponse();
    }
}
