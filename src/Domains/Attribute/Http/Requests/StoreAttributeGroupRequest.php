<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class StoreAttributeGroupRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AttributeGroupModel::class);
    }

    public function rules(): array
    {
        return CreateAttributeGroupData::rules();
    }

    public function persist(): JsonResponse
    {
        $group = app(CreateAttributeGroupAction::class)->execute(CreateAttributeGroupData::fromValidated($this->validated()));

        return (new AttributeGroupResource($group))->response()->setStatusCode(201);
    }
}
