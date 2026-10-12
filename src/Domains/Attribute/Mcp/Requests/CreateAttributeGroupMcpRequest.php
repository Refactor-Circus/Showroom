<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class CreateAttributeGroupMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AttributeGroupModel::class);
    }

    protected function rules(): array
    {
        return CreateAttributeGroupData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $group = app(CreateAttributeGroupAction::class)->execute(CreateAttributeGroupData::fromValidated($validated));

        return (new AttributeGroupResource($group))->toStructuredResponse();
    }
}
