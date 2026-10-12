<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Asset\Actions\CreateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class CreateAssetMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AssetModel::class);
    }

    protected function rules(): array
    {
        return CreateAssetData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $asset = app(CreateAssetAction::class)->execute(CreateAssetData::fromValidated($validated));

        return (new AssetResource($asset))->toStructuredResponse();
    }
}
