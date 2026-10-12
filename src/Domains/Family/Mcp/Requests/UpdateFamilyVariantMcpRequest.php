<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Family\Actions\UpdateFamilyVariantAction;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyVariantData;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

final class UpdateFamilyVariantMcpRequest extends FamilyVariantRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->familyVariant());
    }

    protected function rules(): array
    {
        return [
            'family_variant' => ['required', 'string', 'max:191'],
        ] + UpdateFamilyVariantData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $familyVariant = app(UpdateFamilyVariantAction::class)->execute($this->familyVariant(), UpdateFamilyVariantData::fromValidated($validated));

        return (new FamilyVariantResource($familyVariant))->toStructuredResponse();
    }
}
