<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeOptionAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

final class StoreAttributeOptionRequest extends AttributeRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->catalogAttribute()) && $this->allows('create', AttributeOptionModel::class);
    }

    public function rules(): array
    {
        return CreateAttributeOptionData::rules();
    }

    public function persist(): JsonResponse
    {
        $option = app(CreateAttributeOptionAction::class)->execute($this->catalogAttribute(), CreateAttributeOptionData::fromValidated($this->validated()));

        return (new AttributeOptionResource($option))->response()->setStatusCode(201);
    }
}
