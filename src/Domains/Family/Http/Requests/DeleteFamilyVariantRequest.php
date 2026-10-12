<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Family\Actions\DeleteFamilyVariantAction;

final class DeleteFamilyVariantRequest extends FamilyVariantRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->familyVariant());
    }

    public function persist(): JsonResponse
    {
        app(DeleteFamilyVariantAction::class)->execute($this->familyVariant());

        return new JsonResponse(null, 204);
    }
}
