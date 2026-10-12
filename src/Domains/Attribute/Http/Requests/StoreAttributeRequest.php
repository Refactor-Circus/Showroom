<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

final class StoreAttributeRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AttributeModel::class);
    }

    public function rules(): array
    {
        return CreateAttributeData::rules();
    }

    public function persist(): JsonResponse
    {
        $attribute = app(CreateAttributeAction::class)->execute(CreateAttributeData::fromValidated($this->validated()));

        return (new AttributeResource($attribute))->response()->setStatusCode(201);
    }
}
