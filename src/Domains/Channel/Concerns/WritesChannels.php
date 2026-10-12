<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Concerns;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateChannelData;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * Labels, locales, currencies and category tree, shared by creating and updating a channel.
 */
trait WritesChannels
{
    /**
     * Only the fields given change; a new channel takes every field.
     * Returns whether anything products depend on changed: the code, locales,
     * currencies or category tree, not the labels.
     *
     * @throws ValidationException
     */
    private function writeChannel(ChannelModel $channel, CreateChannelData|UpdateChannelData $data): bool
    {
        $new = ! $channel->exists;

        if ($data->has('labels')) {
            $channel->labels = is_array($data->labels) ? $data->labels : [];
        }

        if ($data->has('currencies')) {
            $channel->currencies = is_array($data->currencies) ? array_values($data->currencies) : [];
        }

        if ($data->has('category_tree')) {
            $tree = is_string($data->categoryTree) ? CategoryModel::query()->where('code', $data->categoryTree)->firstOrFail() : null;

            if ($tree !== null && $tree->parent_id !== null) {
                throw ValidationException::withMessages([
                    'category_tree' => sprintf('Category "%s" is not the root of a tree.', $tree->code),
                ]);
            }

            $channel->categoryTree()->associate($tree);
        }

        $channel->save();

        $changed = $new || $channel->wasChanged(['code', 'currencies', 'category_tree_id']);

        if (is_array($data->locales)) {
            $synced = $channel->locales()->sync(LocaleModel::query()->whereIn('code', $data->locales)->pluck('id')->all());

            $changed = $changed || $synced['attached'] !== [] || $synced['detached'] !== [];
        }

        return $changed;
    }
}
