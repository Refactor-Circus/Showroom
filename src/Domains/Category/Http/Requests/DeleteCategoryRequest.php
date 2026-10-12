<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Category\Actions\DeleteCategoryAction;

final class DeleteCategoryRequest extends CategoryRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->category());
    }

    public function persist(): JsonResponse
    {
        app(DeleteCategoryAction::class)->execute($this->category());

        return new JsonResponse(null, 204);
    }
}
