<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Family\Actions\ListFamiliesAction;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamiliesData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class IndexFamiliesRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', FamilyModel::class);
    }

    public function rules(): array
    {
        return ListFamiliesData::rules();
    }

    public function persist(): JsonResponse
    {
        $families = app(ListFamiliesAction::class)->execute(ListFamiliesData::fromValidated($this->validated()));

        return FamilyResource::collection($families)->response();
    }
}
