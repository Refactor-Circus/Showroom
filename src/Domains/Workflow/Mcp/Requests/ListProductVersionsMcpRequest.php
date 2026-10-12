<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Product\Mcp\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Workflow\Actions\ListProductVersionsAction;
use RefactorCircus\Showroom\Domains\Workflow\Data\ListProductVersionsData;
use RefactorCircus\Showroom\Domains\Workflow\Resources\VersionSummaryResource;

final class ListProductVersionsMcpRequest extends ProductRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->product());
    }

    protected function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:191'],
        ] + ListProductVersionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $versions = app(ListProductVersionsAction::class)->execute($this->product(), ListProductVersionsData::fromValidated($validated));

        return VersionSummaryResource::collection($versions)->toStructuredResponse();
    }
}
