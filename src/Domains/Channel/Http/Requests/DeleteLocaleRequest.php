<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Channel\Actions\DeleteLocaleAction;

final class DeleteLocaleRequest extends LocaleRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->locale());
    }

    public function persist(): JsonResponse
    {
        app(DeleteLocaleAction::class)->execute($this->locale());

        return new JsonResponse(null, 204);
    }
}
