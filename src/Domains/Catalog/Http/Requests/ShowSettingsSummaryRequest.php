<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Catalog\Actions\ShowSettingsSummaryAction;
use RefactorCircus\Showroom\Domains\Catalog\Resources\SettingsSummaryResource;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

final class ShowSettingsSummaryRequest extends Request
{
    /**
     * Showroom has no ability of its own for its settings, so reading them
     * takes being a signed-in catalog reader: one who may list products
     * (D-005).
     */
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModel::class);
    }

    public function persist(): JsonResponse
    {
        $summary = app(ShowSettingsSummaryAction::class)->execute();

        return (new SettingsSummaryResource($summary))->response();
    }
}
