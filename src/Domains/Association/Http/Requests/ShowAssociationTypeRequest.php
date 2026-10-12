<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Association\Actions\ShowAssociationTypeAction;
use RefactorCircus\Showroom\Domains\Association\Resources\AssociationTypeResource;

final class ShowAssociationTypeRequest extends AssociationTypeRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->associationType());
    }

    public function persist(): JsonResponse
    {
        $associationType = app(ShowAssociationTypeAction::class)->execute($this->associationType());

        return (new AssociationTypeResource($associationType))->response();
    }
}
