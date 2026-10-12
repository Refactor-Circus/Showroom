<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Channel\Actions\ShowChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

final class ShowChannelRequest extends ChannelRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->channel());
    }

    public function persist(): JsonResponse
    {
        $channel = app(ShowChannelAction::class)->execute($this->channel());

        return (new ChannelResource($channel))->response();
    }
}
