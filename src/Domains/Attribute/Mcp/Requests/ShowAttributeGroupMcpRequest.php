<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ShowAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class ShowAttributeGroupMcpRequest extends AttributeGroupRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->group());
    }

    protected function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $group = app(ShowAttributeGroupAction::class)->execute($this->group());

        return (new AttributeGroupResource($group))->toStructuredResponse();
    }
}
