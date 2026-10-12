<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Product\Http\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;
use RefactorCircus\Showroom\Domains\Workflow\Actions\RevertProductAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\RevertProductData;

final class RevertProductRequest extends ProductRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->product());
    }

    public function rules(): array
    {
        return RevertProductData::rules();
    }

    public function persist(): JsonResponse
    {
        $product = app(RevertProductAction::class)->execute($this->product(), RevertProductData::fromValidated($this->validated()));

        return (new ProductResource($product))->response();
    }
}
