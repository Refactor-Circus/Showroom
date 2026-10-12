<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Channel\Actions\ListChannelsAction;
use RefactorCircus\Showroom\Domains\Channel\Data\ListChannelsData;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Resources\ChannelResource;

final class IndexChannelsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ChannelModel::class);
    }

    public function rules(): array
    {
        return ListChannelsData::rules();
    }

    public function persist(): JsonResponse
    {
        $channels = app(ListChannelsAction::class)->execute(ListChannelsData::fromValidated($this->validated()));

        return ChannelResource::collection($channels)->response();
    }
}
