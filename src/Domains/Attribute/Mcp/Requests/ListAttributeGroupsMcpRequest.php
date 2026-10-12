<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ListAttributeGroupsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeGroupsData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class ListAttributeGroupsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', AttributeGroupModel::class);
    }

    protected function rules(): array
    {
        return ListAttributeGroupsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $groups = app(ListAttributeGroupsAction::class)->execute(ListAttributeGroupsData::fromValidated($validated));

        return AttributeGroupResource::collection($groups)->toStructuredResponse();
    }
}
