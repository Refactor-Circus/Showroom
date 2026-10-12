<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Family\Actions\ListFamiliesAction;
use RefactorCircus\Showroom\Domains\Family\Data\ListFamiliesData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class ListFamiliesMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', FamilyModel::class);
    }

    protected function rules(): array
    {
        return ListFamiliesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $families = app(ListFamiliesAction::class)->execute(ListFamiliesData::fromValidated($validated));

        return FamilyResource::collection($families)->toStructuredResponse();
    }
}
