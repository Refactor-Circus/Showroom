<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\ProductModel\Actions\ListProductModelsAction;
use RefactorCircus\Showroom\Domains\ProductModel\Data\ListProductModelsData;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\ProductModel\Resources\ProductModelResource;

final class ListProductModelsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModelModel::class);
    }

    protected function rules(): array
    {
        return ListProductModelsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $productModels = app(ListProductModelsAction::class)->execute(ListProductModelsData::fromValidated($validated));

        return ProductModelResource::collection($productModels)->toStructuredResponse();
    }
}
