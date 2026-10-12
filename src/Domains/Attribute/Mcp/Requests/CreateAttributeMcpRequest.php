<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

final class CreateAttributeMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', AttributeModel::class);
    }

    protected function rules(): array
    {
        return CreateAttributeData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $attribute = app(CreateAttributeAction::class)->execute(CreateAttributeData::fromValidated($validated));

        return (new AttributeResource($attribute))->toStructuredResponse();
    }
}
