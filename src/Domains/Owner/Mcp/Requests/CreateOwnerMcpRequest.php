<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;

final class CreateOwnerMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', OwnerModel::class);
    }

    protected function rules(): array
    {
        return CreateOwnerData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $owner = app(CreateOwnerAction::class)->execute(CreateOwnerData::fromValidated($validated));

        return (new OwnerResource($owner))->toStructuredResponse();
    }
}
