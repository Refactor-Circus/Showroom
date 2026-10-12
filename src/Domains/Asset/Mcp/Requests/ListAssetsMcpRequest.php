<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Asset\Actions\ListAssetsAction;
use RefactorCircus\Showroom\Domains\Asset\Data\ListAssetsData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class ListAssetsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', AssetModel::class);
    }

    protected function rules(): array
    {
        return ListAssetsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $assets = app(ListAssetsAction::class)->execute(ListAssetsData::fromValidated($validated));

        return AssetResource::collection($assets)->toStructuredResponse();
    }
}
