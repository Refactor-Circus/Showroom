<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ListAttributeGroupsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeGroupsData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class IndexAttributeGroupsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', AttributeGroupModel::class);
    }

    public function rules(): array
    {
        return ListAttributeGroupsData::rules();
    }

    public function persist(): JsonResponse
    {
        $groups = app(ListAttributeGroupsAction::class)->execute(ListAttributeGroupsData::fromValidated($this->validated()));

        return AttributeGroupResource::collection($groups)->response();
    }
}
