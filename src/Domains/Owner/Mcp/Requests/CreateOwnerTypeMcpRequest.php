<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

final class CreateOwnerTypeMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', OwnerTypeModel::class);
    }

    protected function rules(): array
    {
        return CreateOwnerTypeData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $ownerType = app(CreateOwnerTypeAction::class)->execute(CreateOwnerTypeData::fromValidated($validated));

        return (new OwnerTypeResource($ownerType))->toStructuredResponse();
    }
}
