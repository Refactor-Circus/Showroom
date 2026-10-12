<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Family\Actions\ShowFamilyVariantAction;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

final class ShowFamilyVariantRequest extends FamilyVariantRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->familyVariant());
    }

    public function persist(): JsonResponse
    {
        $familyVariant = app(ShowFamilyVariantAction::class)->execute($this->familyVariant());

        return (new FamilyVariantResource($familyVariant))->response();
    }
}
