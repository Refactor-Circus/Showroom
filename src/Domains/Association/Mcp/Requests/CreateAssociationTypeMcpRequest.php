<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Association\Actions\CreateAssociationTypeAction;
use RefactorCircus\Showroom\Domains\Association\Data\CreateAssociationTypeData;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

final class CreateAssociationTypeMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AssociationTypeModel::class);
    }

    protected function rules(): array
    {
        return CreateAssociationTypeData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $associationType = app(CreateAssociationTypeAction::class)->execute(CreateAssociationTypeData::fromValidated($validated));

        return (new AssociationTypeResource($associationType))->toStructuredResponse();
    }
}
