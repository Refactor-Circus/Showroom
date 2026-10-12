<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Channel\Actions\UpdateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

final class UpdateChannelRequest extends ChannelRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->channel());
    }

    public function rules(): array
    {
        return UpdateChannelData::rules();
    }

    public function persist(): JsonResponse
    {
        $channel = app(UpdateChannelAction::class)->execute($this->channel(), UpdateChannelData::fromValidated($this->validated()));

        return (new ChannelResource($channel))->response();
    }
}
