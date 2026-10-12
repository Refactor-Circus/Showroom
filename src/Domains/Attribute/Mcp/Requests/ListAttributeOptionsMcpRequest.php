<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ListAttributeOptionsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

final class ListAttributeOptionsMcpRequest extends AttributeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->catalogAttribute()) && $this->allows('viewAny', AttributeOptionModel::class);
    }

    protected function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:100'],
        ] + ListAttributeOptionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $options = app(ListAttributeOptionsAction::class)->execute($this->catalogAttribute(), ListAttributeOptionsData::fromValidated($validated));

        return AttributeOptionResource::collection($options)->toStructuredResponse();
    }
}
