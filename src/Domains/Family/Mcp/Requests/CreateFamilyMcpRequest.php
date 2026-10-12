<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class CreateFamilyMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', FamilyModel::class);
    }

    protected function rules(): array
    {
        return CreateFamilyData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $family = app(CreateFamilyAction::class)->execute(CreateFamilyData::fromValidated($validated));

        return (new FamilyResource($family))->toStructuredResponse();
    }
}
