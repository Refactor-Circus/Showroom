<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Asset\Actions\ShowAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class ShowAssetRequest extends AssetRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->asset());
    }

    public function persist(): JsonResponse
    {
        $asset = app(ShowAssetAction::class)->execute($this->asset());

        return (new AssetResource($asset))->response();
    }
}
