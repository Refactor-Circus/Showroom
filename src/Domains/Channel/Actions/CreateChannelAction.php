<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Channel\Concerns\WritesChannels;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;

final class CreateChannelAction
{
    use WritesChannels;

    /**
     * @throws ValidationException
     */
    public function execute(CreateChannelData $data): ChannelModel
    {
        ChannelCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        ChannelCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateChannelData $data): ChannelModel
    {
        return DB::transaction(function () use ($data): ChannelModel {
            $channel = new ChannelModel(['code' => $data->code, 'labels' => [], 'currencies' => []]);

            // Every product's completeness depends on the channels; a label
            // alone changes none. A queued job lists the products, once
            // this commits, rather than this request walking the catalogue.
            if ($this->writeChannel($channel, $data)) {
                app(ProductIndex::class)->queueWithin(SyncScope::All);
            }

            return $channel->load(['locales', 'categoryTree']);
        });
    }
}
