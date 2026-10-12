<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ListAttributeOptionsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

final class IndexAttributeOptionsRequest extends AttributeRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->catalogAttribute()) && $this->allows('viewAny', AttributeOptionModel::class);
    }

    public function rules(): array
    {
        return ListAttributeOptionsData::rules();
    }

    public function persist(): JsonResponse
    {
        $options = app(ListAttributeOptionsAction::class)->execute($this->catalogAttribute(), ListAttributeOptionsData::fromValidated($this->validated()));

        return AttributeOptionResource::collection($options)->response();
    }
}
