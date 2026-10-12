<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Asset\Actions\DeleteAssetAction;

final class DeleteAssetRequest extends AssetRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->asset());
    }

    public function persist(): JsonResponse
    {
        app(DeleteAssetAction::class)->execute($this->asset());

        return new JsonResponse(null, 204);
    }
}
