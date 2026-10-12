<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeOptionAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

final class CreateAttributeOptionMcpRequest extends AttributeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->catalogAttribute()) && $this->allows('create', AttributeOptionModel::class);
    }

    protected function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:100'],
        ] + CreateAttributeOptionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $option = app(CreateAttributeOptionAction::class)->execute($this->catalogAttribute(), CreateAttributeOptionData::fromValidated($validated));

        return (new AttributeOptionResource($option))->toStructuredResponse();
    }
}
