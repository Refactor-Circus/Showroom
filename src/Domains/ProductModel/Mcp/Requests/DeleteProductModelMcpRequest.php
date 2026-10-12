<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\DeleteProductModelAction;

final class DeleteProductModelMcpRequest extends ProductModelRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->productModel());
    }

    protected function rules(): array
    {
        return [
            'product_model' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $productModel = app(DeleteProductModelAction::class)->execute($this->productModel());

        return Response::structured(['deleted' => true, 'code' => $productModel->code]);
    }
}
