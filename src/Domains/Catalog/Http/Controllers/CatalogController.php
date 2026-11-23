<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Catalog\Http\Requests\SearchCatalogRequest;
use RefactorCircus\Showroom\Domains\Catalog\Http\Requests\ShowCatalogStatsRequest;
use RefactorCircus\Showroom\Domains\Catalog\Http\Requests\ShowSettingsSummaryRequest;

final class CatalogController
{
    public function stats(ShowCatalogStatsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function search(SearchCatalogRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function settingsSummary(ShowSettingsSummaryRequest $request): JsonResponse
    {
        return $request->persist();
    }
}
