<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Actions\ListOwnersAction;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnersData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;

final class IndexOwnersRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', OwnerModel::class);
    }

    public function rules(): array
    {
        return ListOwnersData::rules();
    }

    public function persist(): JsonResponse
    {
        $owners = app(ListOwnersAction::class)->execute(ListOwnersData::fromValidated($this->validated()));

        return OwnerResource::collection($owners)->response();
    }
}
