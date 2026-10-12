<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Asset\Actions\DeleteAssetAction;

final class DeleteAssetMcpRequest extends AssetRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->asset());
    }

    protected function rules(): array
    {
        return [
            'asset' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $asset = app(DeleteAssetAction::class)->execute($this->asset());

        return Response::structured(['deleted' => true, 'code' => $asset->code]);
    }
}
