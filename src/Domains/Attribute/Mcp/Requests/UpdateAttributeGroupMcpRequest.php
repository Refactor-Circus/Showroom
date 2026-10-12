<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpdateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class UpdateAttributeGroupMcpRequest extends AttributeGroupRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->group());
    }

    protected function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100'],
        ] + UpdateAttributeGroupData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $group = app(UpdateAttributeGroupAction::class)->execute($this->group(), UpdateAttributeGroupData::fromValidated($validated));

        return (new AttributeGroupResource($group))->toStructuredResponse();
    }
}
