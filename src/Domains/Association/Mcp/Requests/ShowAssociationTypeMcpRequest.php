<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Association\Actions\ShowAssociationTypeAction;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

final class ShowAssociationTypeMcpRequest extends AssociationTypeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->associationType());
    }

    protected function rules(): array
    {
        return [
            'association_type' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $associationType = app(ShowAssociationTypeAction::class)->execute($this->associationType());

        return (new AssociationTypeResource($associationType))->toStructuredResponse();
    }
}
