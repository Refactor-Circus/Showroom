<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Category\Actions\UpdateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Data\UpdateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

final class UpdateCategoryMcpRequest extends CategoryRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->category());
    }

    protected function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:100'],
        ] + UpdateCategoryData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $category = app(UpdateCategoryAction::class)->execute($this->category(), UpdateCategoryData::fromValidated($validated));

        return (new CategoryResource($category))->toStructuredResponse();
    }
}
