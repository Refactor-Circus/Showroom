<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

final class CreateChannelMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', ChannelModel::class);
    }

    protected function rules(): array
    {
        return CreateChannelData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $channel = app(CreateChannelAction::class)->execute(CreateChannelData::fromValidated($validated));

        return (new ChannelResource($channel))->toStructuredResponse();
    }
}
