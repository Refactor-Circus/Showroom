<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Domains\Workflow\Data\TransitionProductData;
use RefactorCircus\Showroom\Domains\Workflow\Enums\Transition;
use RefactorCircus\Showroom\Domains\Workflow\Events\ProductTransitionedActionEvent;
use RefactorCircus\Showroom\Domains\Workflow\Events\ProductTransitioningActionEvent;
use RefactorCircus\Showroom\Domains\Workflow\Exceptions\InvalidTransitionException;
use RefactorCircus\Showroom\Domains\Workflow\Services\CompletenessCalculator;
use RefactorCircus\Showroom\Domains\Workflow\Services\Versions;

final class TransitionProductAction
{
    public function __construct(
        private readonly Versions $versions,
        private readonly ProductIndex $index,
        private readonly CompletenessCalculator $completeness,
    ) {}

    /**
     * @throws InvalidTransitionException
     */
    public function execute(ProductModel $product, TransitionProductData $data): ProductModel
    {
        ProductTransitioningActionEvent::dispatch($product, $data);

        $result = $this->perform($product, $data);

        ProductTransitionedActionEvent::dispatch($result, $data);

        return $result;
    }

    private function perform(ProductModel $product, TransitionProductData $data): ProductModel
    {
        $transition = $data->transition;
        $comment = $data->comment;
        $from = $transition->startsFrom(config('showroom.workflow.require_approval', true) !== false);

        if (! in_array($product->status, $from, true)) {
            throw InvalidTransitionException::from($product->identifier, $transition, $product->status, $from);
        }

        if ($transition === Transition::Submit && config('showroom.workflow.require_complete', false) === true) {
            $this->checkComplete($product);
        }

        return DB::transaction(function () use ($product, $transition, $comment): ProductModel {
            $product->status = $transition->to() ?? $product->status;

            if ($transition === Transition::Unpublish || $transition === Transition::Archive) {
                $product->published_version = null;
                $product->published_at = null;
            }

            $product->save();

            $version = $this->versions->record($product, $transition->action(), $comment);

            if ($transition === Transition::Publish) {
                $product->published_version = $version->version;
                $product->published_at = now();
                $product->save();
            }

            $this->index->queue([$product->id]);

            return $product;
        });
    }

    /**
     * Every channel and locale must be at 100%, freshly computed.
     *
     * @throws InvalidTransitionException
     */
    private function checkComplete(ProductModel $product): void
    {
        $product->loadMissing(['family', 'parent.parent']);
        $rows = $this->completeness->compute($product);

        $channels = ChannelModel::query()->pluck('code', 'id');
        $locales = LocaleModel::query()->pluck('code', 'id');

        $incomplete = [];

        foreach ($rows as $row) {
            if ($row['ratio'] < 100) {
                $incomplete[] = $channels->get($row['channel_id']).' / '.$locales->get($row['locale_id']);
            }
        }

        if ($rows === [] || $incomplete !== []) {
            throw InvalidTransitionException::incomplete($product->identifier, $incomplete);
        }
    }
}
