<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class StoreFamilyRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', FamilyModel::class);
    }

    public function rules(): array
    {
        return CreateFamilyData::rules();
    }

    public function persist(): JsonResponse
    {
        $family = app(CreateFamilyAction::class)->execute(CreateFamilyData::fromValidated($this->validated()));

        return (new FamilyResource($family))->response()->setStatusCode(201);
    }
}
