<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerAction;
use RefactorCircus\Showroom\Support\RecordLookup;

final class DeleteOwnerRequest extends OwnerRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->owner());
    }

    public function rules(): array
    {
        return RecordLookup::rules();
    }

    public function persist(): JsonResponse
    {
        app(DeleteOwnerAction::class)->execute($this->owner());

        return new JsonResponse(null, 204);
    }
}
