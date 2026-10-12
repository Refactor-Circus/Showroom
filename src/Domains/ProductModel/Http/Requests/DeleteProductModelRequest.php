<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\DeleteProductModelAction;

final class DeleteProductModelRequest extends ProductModelRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->productModel());
    }

    public function persist(): JsonResponse
    {
        app(DeleteProductModelAction::class)->execute($this->productModel());

        return new JsonResponse(null, 204);
    }
}
