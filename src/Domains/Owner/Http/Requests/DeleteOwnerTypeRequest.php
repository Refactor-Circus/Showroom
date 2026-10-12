<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerTypeAction;

final class DeleteOwnerTypeRequest extends OwnerTypeRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->ownerType());
    }

    public function persist(): JsonResponse
    {
        app(DeleteOwnerTypeAction::class)->execute($this->ownerType());

        return new JsonResponse(null, 204);
    }
}
