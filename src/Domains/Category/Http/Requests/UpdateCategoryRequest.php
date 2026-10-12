<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Category\Actions\UpdateCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Data\UpdateCategoryData;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

final class UpdateCategoryRequest extends CategoryRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->category());
    }

    public function rules(): array
    {
        return UpdateCategoryData::rules();
    }

    public function persist(): JsonResponse
    {
        $category = app(UpdateCategoryAction::class)->execute($this->category(), UpdateCategoryData::fromValidated($this->validated()));

        return (new CategoryResource($category))->response();
    }
}
