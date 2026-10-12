<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Family\Actions\UpdateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class UpdateFamilyRequest extends FamilyRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->family());
    }

    public function rules(): array
    {
        return UpdateFamilyData::rules();
    }

    public function persist(): JsonResponse
    {
        $family = app(UpdateFamilyAction::class)->execute($this->family(), UpdateFamilyData::fromValidated($this->validated()));

        return (new FamilyResource($family))->response();
    }
}
