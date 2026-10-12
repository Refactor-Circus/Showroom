<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Asset\Actions\UpdateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\UpdateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class UpdateAssetMcpRequest extends AssetRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->asset());
    }

    protected function rules(): array
    {
        return [
            'asset' => ['required', 'string', 'max:191'],
        ] + UpdateAssetData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $asset = app(UpdateAssetAction::class)->execute($this->asset(), UpdateAssetData::fromValidated($validated));

        return (new AssetResource($asset))->toStructuredResponse();
    }
}
