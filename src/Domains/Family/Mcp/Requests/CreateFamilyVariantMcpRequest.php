<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyVariantAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyVariantData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

final class CreateFamilyVariantMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', FamilyVariantModel::class);
    }

    protected function rules(): array
    {
        return CreateFamilyVariantData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $familyVariant = app(CreateFamilyVariantAction::class)->execute(CreateFamilyVariantData::fromValidated($validated));

        return (new FamilyVariantResource($familyVariant))->toStructuredResponse();
    }
}
