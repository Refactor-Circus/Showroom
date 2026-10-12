<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Association\Actions\UpdateAssociationTypeAction;
use RefactorCircus\Showroom\Domains\Association\Data\UpdateAssociationTypeData;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

final class UpdateAssociationTypeMcpRequest extends AssociationTypeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->associationType());
    }

    protected function rules(): array
    {
        return [
            'association_type' => ['required', 'string', 'max:100'],
        ] + UpdateAssociationTypeData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $associationType = app(UpdateAssociationTypeAction::class)->execute($this->associationType(), UpdateAssociationTypeData::fromValidated($validated));

        return (new AssociationTypeResource($associationType))->toStructuredResponse();
    }
}
