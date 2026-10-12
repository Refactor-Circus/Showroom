<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Association\Actions\ListAssociationTypesAction;
use RefactorCircus\Showroom\Domains\Association\Data\ListAssociationTypesData;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

final class ListAssociationTypesMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', AssociationTypeModel::class);
    }

    protected function rules(): array
    {
        return ListAssociationTypesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $associationTypes = app(ListAssociationTypesAction::class)->execute(ListAssociationTypesData::fromValidated($validated));

        return AssociationTypeResource::collection($associationTypes)->toStructuredResponse();
    }
}
