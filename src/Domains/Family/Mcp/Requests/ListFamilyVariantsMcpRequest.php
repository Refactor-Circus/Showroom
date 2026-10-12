<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Family\Actions\ListFamilyVariantsAction;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamilyVariantsData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyVariantResource;

final class ListFamilyVariantsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', FamilyVariantModel::class);
    }

    protected function rules(): array
    {
        return ListFamilyVariantsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $familyVariants = app(ListFamilyVariantsAction::class)->execute(ListFamilyVariantsData::fromValidated($validated));

        return FamilyVariantResource::collection($familyVariants)->toStructuredResponse();
    }
}
