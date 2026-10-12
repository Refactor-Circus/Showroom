<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Family\Actions\ShowFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class ShowFamilyMcpRequest extends FamilyRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->family());
    }

    protected function rules(): array
    {
        return [
            'family' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $family = app(ShowFamilyAction::class)->execute($this->family());

        return (new FamilyResource($family))->toStructuredResponse();
    }
}
