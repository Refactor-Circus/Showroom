<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Asset\Actions\UpdateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\UpdateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class UpdateAssetRequest extends AssetRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->asset());
    }

    public function rules(): array
    {
        return UpdateAssetData::rules();
    }

    public function persist(): JsonResponse
    {
        $asset = app(UpdateAssetAction::class)->execute($this->asset(), UpdateAssetData::fromValidated($this->validated()));

        return (new AssetResource($asset))->response();
    }
}
