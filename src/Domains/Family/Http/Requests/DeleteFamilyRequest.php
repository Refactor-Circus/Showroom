<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Family\Actions\DeleteFamilyAction;

final class DeleteFamilyRequest extends FamilyRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->family());
    }

    public function persist(): JsonResponse
    {
        app(DeleteFamilyAction::class)->execute($this->family());

        return new JsonResponse(null, 204);
    }
}
