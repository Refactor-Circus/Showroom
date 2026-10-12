<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Channel\Actions\UpdateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

final class UpdateChannelMcpRequest extends ChannelRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->channel());
    }

    protected function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'max:100'],
        ] + UpdateChannelData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $channel = app(UpdateChannelAction::class)->execute($this->channel(), UpdateChannelData::fromValidated($validated));

        return (new ChannelResource($channel))->toStructuredResponse();
    }
}
