<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Family\Actions\ShowFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class ShowFamilyRequest extends FamilyRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->family());
    }

    public function persist(): JsonResponse
    {
        $family = app(ShowFamilyAction::class)->execute($this->family());

        return (new FamilyResource($family))->response();
    }
}
