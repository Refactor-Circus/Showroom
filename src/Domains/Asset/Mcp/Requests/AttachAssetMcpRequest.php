<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Asset\Actions\AttachAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\AttachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class AttachAssetMcpRequest extends AssetRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->asset());
    }

    protected function rules(): array
    {
        return [
            'asset' => ['required', 'string', 'max:191'],
        ] + AttachAssetData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $asset = app(AttachAssetAction::class)->execute($this->asset(), AttachAssetData::fromValidated($validated));

        return (new AssetResource($asset))->toStructuredResponse();
    }
}
