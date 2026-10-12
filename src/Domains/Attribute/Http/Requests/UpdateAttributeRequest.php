<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpdateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

final class UpdateAttributeRequest extends AttributeRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->catalogAttribute());
    }

    public function rules(): array
    {
        return UpdateAttributeData::rules();
    }

    public function persist(): JsonResponse
    {
        $attribute = app(UpdateAttributeAction::class)->execute($this->catalogAttribute(), UpdateAttributeData::fromValidated($this->validated()));

        return (new AttributeResource($attribute))->response();
    }
}
