<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Product\Http\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;
use RefactorCircus\Showroom\Domains\Workflow\Actions\TransitionProductAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\TransitionProductData;

final class TransitionProductRequest extends ProductRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->product());
    }

    public function rules(): array
    {
        return TransitionProductData::rules();
    }

    public function persist(): JsonResponse
    {
        $product = app(TransitionProductAction::class)->execute($this->product(), TransitionProductData::fromValidated($this->validated()));

        return (new ProductResource($product))->response();
    }
}
