<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;

final class StoreOwnerRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', OwnerModel::class);
    }

    public function rules(): array
    {
        return CreateOwnerData::rules();
    }

    public function persist(): JsonResponse
    {
        $owner = app(CreateOwnerAction::class)->execute(CreateOwnerData::fromValidated($this->validated()));

        return (new OwnerResource($owner))->response()->setStatusCode(201);
    }
}
