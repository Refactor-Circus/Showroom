<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Asset\Actions\ShowAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class ShowAssetMcpRequest extends AssetRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->asset());
    }

    protected function rules(): array
    {
        return [
            'asset' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $asset = app(ShowAssetAction::class)->execute($this->asset());

        return (new AssetResource($asset))->toStructuredResponse();
    }
}
