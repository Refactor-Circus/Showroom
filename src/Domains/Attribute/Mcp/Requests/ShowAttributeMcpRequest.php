<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ShowAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

final class ShowAttributeMcpRequest extends AttributeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->catalogAttribute());
    }

    protected function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $attribute = app(ShowAttributeAction::class)->execute($this->catalogAttribute());

        return (new AttributeResource($attribute))->toStructuredResponse();
    }
}
