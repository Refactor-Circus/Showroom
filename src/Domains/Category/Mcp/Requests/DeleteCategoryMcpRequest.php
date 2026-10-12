<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Category\Actions\DeleteCategoryAction;

final class DeleteCategoryMcpRequest extends CategoryRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->category());
    }

    protected function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $category = app(DeleteCategoryAction::class)->execute($this->category());

        return Response::structured(['deleted' => true, 'code' => $category->code]);
    }
}
