<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Catalog\Actions\ShowCatalogStatsAction;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogStatsResource;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

final class ShowCatalogStatsRequest extends Request
{
    /**
     * The figures describe the whole catalog, so reading them takes listing
     * products.
     */
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModel::class);
    }

    public function persist(): JsonResponse
    {
        $stats = app(ShowCatalogStatsAction::class)->execute();

        return (new CatalogStatsResource($stats))->response();
    }
}
