<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Actions\ListOwnerTypesAction;
use RefactorCircus\Showroom\Domains\Owner\Data\ListOwnerTypesData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

final class ListOwnerTypesMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', OwnerTypeModel::class);
    }

    protected function rules(): array
    {
        return ListOwnerTypesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $ownerTypes = app(ListOwnerTypesAction::class)->execute(ListOwnerTypesData::fromValidated($validated));

        return OwnerTypeResource::collection($ownerTypes)->toStructuredResponse();
    }
}
