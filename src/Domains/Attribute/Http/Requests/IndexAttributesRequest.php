<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ListAttributesAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributesData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

final class IndexAttributesRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', AttributeModel::class);
    }

    public function rules(): array
    {
        return ListAttributesData::rules();
    }

    public function persist(): JsonResponse
    {
        $attributes = app(ListAttributesAction::class)->execute(ListAttributesData::fromValidated($this->validated()));

        return AttributeResource::collection($attributes)->response();
    }
}
