<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Association\Actions\CreateAssociationTypeAction;
use RefactorCircus\Showroom\Domains\Association\Data\CreateAssociationTypeData;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

final class StoreAssociationTypeRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AssociationTypeModel::class);
    }

    public function rules(): array
    {
        return CreateAssociationTypeData::rules();
    }

    public function persist(): JsonResponse
    {
        $associationType = app(CreateAssociationTypeAction::class)->execute(CreateAssociationTypeData::fromValidated($this->validated()));

        return (new AssociationTypeResource($associationType))->response()->setStatusCode(201);
    }
}
