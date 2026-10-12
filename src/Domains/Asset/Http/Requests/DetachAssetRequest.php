<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Asset\Actions\DetachAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\DetachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class DetachAssetRequest extends AssetRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->asset());
    }

    public function rules(): array
    {
        return DetachAssetData::rules();
    }

    public function persist(): JsonResponse
    {
        $asset = app(DetachAssetAction::class)->execute($this->asset(), DetachAssetData::fromValidated($this->validated()));

        return (new AssetResource($asset))->response();
    }
}
