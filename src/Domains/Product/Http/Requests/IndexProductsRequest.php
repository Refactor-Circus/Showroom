<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Actions\ListProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\ListProductsData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class IndexProductsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModel::class);
    }

    public function rules(): array
    {
        return ListProductsData::rules();
    }

    public function persist(): JsonResponse
    {
        $products = app(ListProductsAction::class)->execute(ListProductsData::fromValidated($this->validated()));

        return ProductResource::collection($products)->additional(['facets' => (object) $products->facets])->response();
    }
}
