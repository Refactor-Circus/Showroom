<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Product\Actions\DeleteProductAction;

final class DeleteProductMcpRequest extends ProductRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->product());
    }

    protected function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $product = app(DeleteProductAction::class)->execute($this->product());

        return Response::structured(['deleted' => true, 'identifier' => $product->identifier]);
    }
}
