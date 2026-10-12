<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateChannelAction;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

final class StoreChannelRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', ChannelModel::class);
    }

    public function rules(): array
    {
        return CreateChannelData::rules();
    }

    public function persist(): JsonResponse
    {
        $channel = app(CreateChannelAction::class)->execute(CreateChannelData::fromValidated($this->validated()));

        return (new ChannelResource($channel))->response()->setStatusCode(201);
    }
}
