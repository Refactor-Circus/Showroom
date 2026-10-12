<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Product\Http\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Workflow\Actions\ShowProductVersionAction;
use RefactorCircus\Showroom\Domains\Workflow\Resources\VersionResource;

final class ShowProductVersionRequest extends ProductRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->product());
    }

    public function persist(): JsonResponse
    {
        $version = app(ShowProductVersionAction::class)->execute($this->product(), (string) $this->route('version'));

        return (new VersionResource($version))->response();
    }
}
