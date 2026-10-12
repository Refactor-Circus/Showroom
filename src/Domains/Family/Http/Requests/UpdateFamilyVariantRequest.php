<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Family\Actions\UpdateFamilyVariantAction;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyVariantData;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

final class UpdateFamilyVariantRequest extends FamilyVariantRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->familyVariant());
    }

    public function rules(): array
    {
        return UpdateFamilyVariantData::rules();
    }

    public function persist(): JsonResponse
    {
        $familyVariant = app(UpdateFamilyVariantAction::class)->execute($this->familyVariant(), UpdateFamilyVariantData::fromValidated($this->validated()));

        return (new FamilyVariantResource($familyVariant))->response();
    }
}
