<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\ListProductModelsAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ListProductModelsData;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

final class IndexProductModelsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModelModel::class);
    }

    public function rules(): array
    {
        return ListProductModelsData::rules();
    }

    public function persist(): JsonResponse
    {
        $productModels = app(ListProductModelsAction::class)->execute(ListProductModelsData::fromValidated($this->validated()));

        return ProductModelResource::collection($productModels)->response();
    }
}
