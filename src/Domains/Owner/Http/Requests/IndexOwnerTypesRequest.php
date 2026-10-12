<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Actions\ListOwnerTypesAction;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnerTypesData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

final class IndexOwnerTypesRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', OwnerTypeModel::class);
    }

    public function rules(): array
    {
        return ListOwnerTypesData::rules();
    }

    public function persist(): JsonResponse
    {
        $ownerTypes = app(ListOwnerTypesAction::class)->execute(ListOwnerTypesData::fromValidated($this->validated()));

        return OwnerTypeResource::collection($ownerTypes)->response();
    }
}
