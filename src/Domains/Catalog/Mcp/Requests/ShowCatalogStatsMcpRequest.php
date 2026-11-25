<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Catalog\Actions\ShowCatalogStatsAction;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogStatsResource;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

final class ShowCatalogStatsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModel::class);
    }

    protected function handle(array $validated): ResponseFactory
    {
        $stats = app(ShowCatalogStatsAction::class)->execute();

        return (new CatalogStatsResource($stats))->toStructuredResponse();
    }
}
