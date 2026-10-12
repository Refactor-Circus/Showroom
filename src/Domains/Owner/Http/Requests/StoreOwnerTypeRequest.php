<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

final class StoreOwnerTypeRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', OwnerTypeModel::class);
    }

    public function rules(): array
    {
        return CreateOwnerTypeData::rules();
    }

    public function persist(): JsonResponse
    {
        $ownerType = app(CreateOwnerTypeAction::class)->execute(CreateOwnerTypeData::fromValidated($this->validated()));

        return (new OwnerTypeResource($ownerType))->response()->setStatusCode(201);
    }
}
