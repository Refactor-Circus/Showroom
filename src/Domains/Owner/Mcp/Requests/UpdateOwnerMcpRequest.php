<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;

final class UpdateOwnerMcpRequest extends OwnerRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->owner());
    }

    protected function rules(): array
    {
        return [
            'owner' => ['required', 'string', 'max:191'],
        ] + UpdateOwnerData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $owner = app(UpdateOwnerAction::class)->execute($this->owner(), UpdateOwnerData::fromValidated($validated));

        return (new OwnerResource($owner))->toStructuredResponse();
    }
}
