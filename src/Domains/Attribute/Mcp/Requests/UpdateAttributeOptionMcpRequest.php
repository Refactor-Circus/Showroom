<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpdateAttributeOptionAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeOptionResource;

final class UpdateAttributeOptionMcpRequest extends AttributeOptionRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->option());
    }

    protected function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:100'],
            'option' => ['required', 'string', 'max:100'],
        ] + UpdateAttributeOptionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $option = app(UpdateAttributeOptionAction::class)->execute($this->option(), UpdateAttributeOptionData::fromValidated($validated));

        return (new AttributeOptionResource($option))->toStructuredResponse();
    }
}
