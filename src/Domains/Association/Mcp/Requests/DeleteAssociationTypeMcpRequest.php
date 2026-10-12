<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Association\Actions\DeleteAssociationTypeAction;

final class DeleteAssociationTypeMcpRequest extends AssociationTypeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->associationType());
    }

    protected function rules(): array
    {
        return [
            'association_type' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $associationType = app(DeleteAssociationTypeAction::class)->execute($this->associationType());

        return Response::structured(['deleted' => true, 'code' => $associationType->code]);
    }
}
