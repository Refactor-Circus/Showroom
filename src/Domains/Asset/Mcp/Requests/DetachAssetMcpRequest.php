<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Asset\Actions\DetachAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\DetachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class DetachAssetMcpRequest extends AssetRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->asset());
    }

    protected function rules(): array
    {
        return [
            'asset' => ['required', 'string', 'max:191'],
        ] + DetachAssetData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $asset = app(DetachAssetAction::class)->execute($this->asset(), DetachAssetData::fromValidated($validated));

        return (new AssetResource($asset))->toStructuredResponse();
    }
}
