<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;

/**
 * Remove every value slot of a deleted locale or channel from products and
 * product models.
 */
final class PurgeValueSlots implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public ?string $locale = null,
        public ?string $channel = null,
    ) {}

    public function handle(ProductIndex $index): void
    {
        // Slots can sit under any attribute, so every record is read; this
        // runs only when a locale or channel is deleted. Each chunk queues
        // its own syncs, so memory stays flat however large the catalogue.
        ProductModel::query()->whereNotNull('values')->chunkById(200, function (Collection $products) use ($index): void {
            $affected = [];

            foreach ($products as $product) {
                if ($this->purge($product)) {
                    $affected[] = $product->id;
                }
            }

            $index->queue($affected);
        });

        ProductModelModel::query()->whereNotNull('values')->chunkById(200, function (Collection $models) use ($index): void {
            $affected = [];

            foreach ($models as $model) {
                if ($this->purge($model)) {
                    $affected[] = $index->productIdsUnder($model);
                }
            }

            $index->queue(array_merge(...$affected));
        });
    }

    /**
     * Whether anything was removed.
     */
    private function purge(ProductModel|ProductModelModel $record): bool
    {
        $values = $record->ownValues();
        $changed = false;

        foreach ($values as $code => $channels) {
            foreach ($channels as $channel => $locales) {
                if ($this->channel !== null && $channel === $this->channel) {
                    unset($values[$code][$channel]);
                    $changed = true;

                    continue;
                }

                if ($this->locale !== null && array_key_exists($this->locale, $locales)) {
                    unset($values[$code][$channel][$this->locale]);
                    $changed = true;

                    if ($values[$code][$channel] === []) {
                        unset($values[$code][$channel]);
                    }
                }
            }

            if ($values[$code] === []) {
                unset($values[$code]);
            }
        }

        if ($changed) {
            $record->values = $values;
            $record->save();
        }

        return $changed;
    }
}
