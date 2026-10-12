<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Category\Actions\ShowCategoryAction;
use RefactorCircus\Showroom\Domains\Category\Resources\CategoryResource;

final class ShowCategoryRequest extends CategoryRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->category());
    }

    public function persist(): JsonResponse
    {
        $category = app(ShowCategoryAction::class)->execute($this->category());

        return (new CategoryResource($category))->response();
    }
}
