<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Family\Actions\UpdateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Resources\FamilyResource;

final class UpdateFamilyMcpRequest extends FamilyRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->family());
    }

    protected function rules(): array
    {
        return [
            'family' => ['required', 'string', 'max:100'],
        ] + UpdateFamilyData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $family = app(UpdateFamilyAction::class)->execute($this->family(), UpdateFamilyData::fromValidated($validated));

        return (new FamilyResource($family))->toStructuredResponse();
    }
}
