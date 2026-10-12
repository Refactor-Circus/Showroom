<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Owner\Actions\ShowOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;

final class ShowOwnerRequest extends OwnerRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->owner());
    }

    public function persist(): JsonResponse
    {
        $owner = app(ShowOwnerAction::class)->execute($this->owner());

        return (new OwnerResource($owner))->response();
    }
}
