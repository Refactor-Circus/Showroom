<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Family\Actions\ShowFamilyVariantAction;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

final class ShowFamilyVariantMcpRequest extends FamilyVariantRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->familyVariant());
    }

    protected function rules(): array
    {
        return [
            'family_variant' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $familyVariant = app(ShowFamilyVariantAction::class)->execute($this->familyVariant());

        return (new FamilyVariantResource($familyVariant))->toStructuredResponse();
    }
}
