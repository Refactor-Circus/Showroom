<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Scores how much of what a product's family requires it holds, for every
 * channel and each of the channel's locales.
 */
final class CompletenessCalculator
{
    /**
     * @var Collection<int, ChannelModel>|null
     */
    private ?Collection $channels = null;

    /**
     * Each family's required attributes, while refresh() runs.
     *
     * @var array<string, Collection<int, AttributeModel>>|null
     */
    private ?array $members = null;

    /**
     * Rewrite the stored scores of these products. Each needs `family` and
     * the `parent` chain loaded.
     *
     * @param  iterable<int, ProductModel>  $products
     */
    public function refresh(iterable $products): void
    {
        $this->channels = null;
        $this->members = [];

        try {
            $ids = [];
            $rows = [];

            foreach ($products as $product) {
                $ids[] = $product->id;
                array_push($rows, ...$this->compute($product));
            }

            if ($ids === []) {
                return;
            }

            // One delete for the whole batch and inserts of 200 rows (within
            // every driver's binding limit), together, so a reader never sees
            // a product with no scores.
            DB::transaction(function () use ($ids, $rows): void {
                DB::table(Tables::name('product_completeness'))->whereIn('product_id', $ids)->delete();

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table(Tables::name('product_completeness'))->insert($chunk);
                }
            });
        } finally {
            $this->members = null;
        }
    }

    /**
     * @return array<int, array{product_id: string, channel_id: string, locale_id: string, required: int, missing: int, ratio: int, missing_attributes: string, updated_at: Carbon}>
     */
    public function compute(ProductModel $product): array
    {
        $family = $product->family;

        if ($family === null) {
            return [];
        }

        $members = $this->members[$family->id] ?? $family->familyAttributes()->get()->filter(fn (AttributeModel $attribute): bool => (bool) $this->pivot($attribute, 'is_required'));

        if ($this->members !== null) {
            $this->members[$family->id] = $members;
        }
        $values = $product->allValues();
        $rows = [];

        foreach ($this->channels() as $channel) {
            $required = $members->filter(function (AttributeModel $attribute) use ($channel): bool {
                $channels = $this->pivot($attribute, 'required_channels');
                $channels = is_string($channels) ? json_decode($channels, true) : $channels;

                return ! is_array($channels) || in_array($channel->code, $channels, true);
            });

            foreach ($channel->locales as $locale) {
                $missing = $required
                    ->reject(fn (AttributeModel $attribute): bool => $this->filled(Values::get(
                        $values,
                        $attribute->code,
                        $attribute->is_scopable ? $channel->code : null,
                        $attribute->is_localizable ? $locale->code : null,
                    )))
                    ->pluck('code')
                    ->values()
                    ->all();

                $total = $required->count();

                $rows[] = [
                    'product_id' => $product->id,
                    'channel_id' => $channel->id,
                    'locale_id' => $locale->id,
                    'required' => $total,
                    'missing' => count($missing),
                    // Nothing required counts as complete.
                    'ratio' => $total === 0 ? 100 : intdiv(($total - count($missing)) * 100, $total),
                    'missing_attributes' => json_encode($missing, JSON_THROW_ON_ERROR),
                    'updated_at' => now(),
                ];
            }
        }

        return $rows;
    }

    private function filled(mixed $data): bool
    {
        return $data !== null && $data !== '' && $data !== [];
    }

    private function pivot(AttributeModel $attribute, string $key): mixed
    {
        $pivot = $attribute->getRelation('pivot');

        return $pivot instanceof Model ? $pivot->getAttribute($key) : null;
    }

    /**
     * @return Collection<int, ChannelModel>
     */
    private function channels(): Collection
    {
        return $this->channels ??= ChannelModel::query()->with('locales')->orderBy('code')->get();
    }
}
