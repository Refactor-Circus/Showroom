<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Product\Actions\ShowProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\ShowProductData;
use RefactorCircus\Showroom\Domains\Product\Resources\ProductResource;

final class ShowProductRequest extends ProductRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->product());
    }

    public function rules(): array
    {
        return ShowProductData::rules();
    }

    /**
     * Tagged with an ETag of the body, so a client polling a product it
     * already has gets an empty 304 back instead of the product again.
     */
    public function persist(): JsonResponse
    {
        $product = app(ShowProductAction::class)->execute($this->product(), ShowProductData::fromValidated($this->validated()));

        $response = (new ProductResource($product))->response();
        $response->setEtag(hash('xxh3', (string) $response->getContent()));
        $response->isNotModified($this);

        return $response;
    }
}
