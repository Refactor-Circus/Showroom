<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpdateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class UpdateAttributeGroupRequest extends AttributeGroupRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->group());
    }

    public function rules(): array
    {
        return UpdateAttributeGroupData::rules();
    }

    public function persist(): JsonResponse
    {
        $group = app(UpdateAttributeGroupAction::class)->execute($this->group(), UpdateAttributeGroupData::fromValidated($this->validated()));

        return (new AttributeGroupResource($group))->response();
    }
}
