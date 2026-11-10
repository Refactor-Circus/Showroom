<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Product\Http\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Workflow\Actions\ListProductVersionsAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\ListProductVersionsData;
use RefactorCircus\Showroom\Domains\Workflow\Resources\VersionSummaryResource;
use RefactorCircus\Showroom\Support\RecordLookup;

final class IndexProductVersionsRequest extends ProductRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->product());
    }

    public function rules(): array
    {
        return [...ListProductVersionsData::rules(), ...RecordLookup::rules()];
    }

    public function persist(): JsonResponse
    {
        $versions = app(ListProductVersionsAction::class)->execute($this->product(), ListProductVersionsData::fromValidated($this->validated()));

        return VersionSummaryResource::collection($versions)->response();
    }
}
