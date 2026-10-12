<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpdateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

final class UpdateAttributeMcpRequest extends AttributeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->catalogAttribute());
    }

    protected function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:100'],
        ] + UpdateAttributeData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $attribute = app(UpdateAttributeAction::class)->execute($this->catalogAttribute(), UpdateAttributeData::fromValidated($validated));

        return (new AttributeResource($attribute))->toStructuredResponse();
    }
}
