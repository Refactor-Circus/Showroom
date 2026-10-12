<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Asset\Actions\ListAssetsAction;
use RefactorCircus\Showroom\Domains\Asset\Data\ListAssetsData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Resources\AssetResource;

final class IndexAssetsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', AssetModel::class);
    }

    public function rules(): array
    {
        return ListAssetsData::rules();
    }

    public function persist(): JsonResponse
    {
        $assets = app(ListAssetsAction::class)->execute(ListAssetsData::fromValidated($this->validated()));

        return AssetResource::collection($assets)->response();
    }
}
