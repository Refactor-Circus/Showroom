<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Catalog\Http\Requests\ShowCatalogStatsRequest;

final class CatalogController
{
    public function stats(ShowCatalogStatsRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
