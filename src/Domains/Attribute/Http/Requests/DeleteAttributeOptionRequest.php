<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Attribute\Actions\DeleteAttributeOptionAction;

final class DeleteAttributeOptionRequest extends AttributeOptionRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->option());
    }

    public function persist(): JsonResponse
    {
        app(DeleteAttributeOptionAction::class)->execute($this->option());

        return new JsonResponse(null, 204);
    }
}
