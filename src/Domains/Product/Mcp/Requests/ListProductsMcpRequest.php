<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Actions\ListProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\ListProductsData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class ListProductsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModel::class);
    }

    protected function rules(): array
    {
        return ListProductsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $products = app(ListProductsAction::class)->execute(ListProductsData::fromValidated($validated));

        return ProductResource::collection($products)->additional(['facets' => (object) $products->facets])->toStructuredResponse();
    }
}
