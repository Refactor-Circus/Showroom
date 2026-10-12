<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Channel\Actions\ShowChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

final class ShowChannelMcpRequest extends ChannelRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->channel());
    }

    protected function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $channel = app(ShowChannelAction::class)->execute($this->channel());

        return (new ChannelResource($channel))->toStructuredResponse();
    }
}
