<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Channel\Actions\DeleteChannelAction;

final class DeleteChannelRequest extends ChannelRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->channel());
    }

    public function persist(): JsonResponse
    {
        app(DeleteChannelAction::class)->execute($this->channel());

        return new JsonResponse(null, 204);
    }
}
