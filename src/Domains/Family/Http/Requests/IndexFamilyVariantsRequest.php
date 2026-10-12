<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Family\Actions\ListFamilyVariantsAction;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamilyVariantsData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

final class IndexFamilyVariantsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', FamilyVariantModel::class);
    }

    public function rules(): array
    {
        return ListFamilyVariantsData::rules();
    }

    public function persist(): JsonResponse
    {
        $familyVariants = app(ListFamilyVariantsAction::class)->execute(ListFamilyVariantsData::fromValidated($this->validated()));

        return FamilyVariantResource::collection($familyVariants)->response();
    }
}
