<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Product\Mcp\Requests\ProductRequest;
use RefactorCircus\Showroom\Domains\Workflow\Actions\ShowProductVersionAction;
use RefactorCircus\Showroom\Domains\Workflow\Resources\VersionResource;

final class ShowProductVersionMcpRequest extends ProductRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->product());
    }

    protected function rules(): array
    {
        return [
            'product' => ['required', 'string', 'max:191'],
            'version' => ['required', 'string', 'max:20'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $version = app(ShowProductVersionAction::class)->execute($this->product(), (string) $validated['version']);

        return (new VersionResource($version))->toStructuredResponse();
    }
}
