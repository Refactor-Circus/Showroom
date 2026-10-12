<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Owner\Actions\ShowOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

final class ShowOwnerTypeRequest extends OwnerTypeRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->ownerType());
    }

    public function persist(): JsonResponse
    {
        $ownerType = app(ShowOwnerTypeAction::class)->execute($this->ownerType());

        return (new OwnerTypeResource($ownerType))->response();
    }
}
