<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Category\Actions\ShowCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

final class ShowCategoryMcpRequest extends CategoryRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->category());
    }

    protected function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $category = app(ShowCategoryAction::class)->execute($this->category());

        return (new CategoryResource($category))->toStructuredResponse();
    }
}
