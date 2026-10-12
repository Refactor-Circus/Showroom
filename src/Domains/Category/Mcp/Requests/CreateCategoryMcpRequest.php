<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Category\Actions\CreateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Data\CreateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

final class CreateCategoryMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', CategoryModel::class);
    }

    protected function rules(): array
    {
        return CreateCategoryData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $category = app(CreateCategoryAction::class)->execute(CreateCategoryData::fromValidated($validated));

        return (new CategoryResource($category))->toStructuredResponse();
    }
}
