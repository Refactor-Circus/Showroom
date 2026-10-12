<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerTypeAction;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerTypeData;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerTypeResource;

final class UpdateOwnerTypeMcpRequest extends OwnerTypeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->ownerType());
    }

    protected function rules(): array
    {
        return [
            'owner_type' => ['required', 'string', 'max:191'],
        ] + UpdateOwnerTypeData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $ownerType = app(UpdateOwnerTypeAction::class)->execute($this->ownerType(), UpdateOwnerTypeData::fromValidated($validated));

        return (new OwnerTypeResource($ownerType))->toStructuredResponse();
    }
}
