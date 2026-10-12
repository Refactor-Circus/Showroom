<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Channel\Concerns\WritesChannels;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\ChannelUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;

final class UpdateChannelAction
{
    use WritesChannels;

    /**
     * @throws ValidationException
     */
    public function execute(ChannelModel $channel, UpdateChannelData $data): ChannelModel
    {
        ChannelUpdatingActionEvent::dispatch($channel, $data);

        $result = $this->perform($channel, $data);

        ChannelUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(ChannelModel $channel, UpdateChannelData $data): ChannelModel
    {
        return DB::transaction(function () use ($channel, $data): ChannelModel {
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
