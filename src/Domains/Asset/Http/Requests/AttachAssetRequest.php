<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Asset\Actions\AttachAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\AttachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class AttachAssetRequest extends AssetRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->asset());
    }

    public function rules(): array
    {
        return AttachAssetData::rules();
    }

    public function persist(): JsonResponse
    {
        $asset = app(AttachAssetAction::class)->execute($this->asset(), AttachAssetData::fromValidated($this->validated()));

        return (new AssetResource($asset))->response();
    }
}
