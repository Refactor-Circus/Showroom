<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Category\Actions\CreateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Data\CreateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

final class StoreCategoryRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', CategoryModel::class);
    }

    public function rules(): array
    {
        return CreateCategoryData::rules();
    }

    public function persist(): JsonResponse
    {
        $category = app(CreateCategoryAction::class)->execute(CreateCategoryData::fromValidated($this->validated()));

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }
}
