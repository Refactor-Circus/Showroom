<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Flag\Actions\DeleteFlagAction;

final class DeleteFlagRequest extends FlagRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->flag());
    }

    public function persist(): JsonResponse
    {
        app(DeleteFlagAction::class)->execute($this->flag());

        return new JsonResponse(null, 204);
    }
}
