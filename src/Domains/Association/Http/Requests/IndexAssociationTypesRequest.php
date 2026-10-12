<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Association\Actions\ListAssociationTypesAction;
use RefactorCircus\Showroom\Domains\Association\Data\ListAssociationTypesData;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

final class IndexAssociationTypesRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', AssociationTypeModel::class);
    }

    public function rules(): array
    {
        return ListAssociationTypesData::rules();
    }

    public function persist(): JsonResponse
    {
        $associationTypes = app(ListAssociationTypesAction::class)->execute(ListAssociationTypesData::fromValidated($this->validated()));

        return AssociationTypeResource::collection($associationTypes)->response();
    }
}
