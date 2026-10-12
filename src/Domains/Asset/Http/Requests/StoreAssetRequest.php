<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Asset\Actions\CreateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class StoreAssetRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AssetModel::class);
    }

    public function rules(): array
    {
        return CreateAssetData::rules();
    }

    public function persist(): JsonResponse
    {
        $asset = app(CreateAssetAction::class)->execute(CreateAssetData::fromValidated($this->validated()));

        return (new AssetResource($asset))->response()->setStatusCode(201);
    }
}
