<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Channel\Actions\DeleteChannelAction;

final class DeleteChannelMcpRequest extends ChannelRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->channel());
    }

    protected function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $channel = app(DeleteChannelAction::class)->execute($this->channel());

        return Response::structured(['deleted' => true, 'code' => $channel->code]);
    }
}
