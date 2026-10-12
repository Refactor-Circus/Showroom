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
 * Remove a deleted attribute's values — or a deleted option's — from every
 * product and product model that stores them.
 */
final class PurgeAttributeValues implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public string $attribute,
        public ?string $option = null,
    ) {}

    public function handle(ProductIndex $index): void
    {
        // Each chunk queues its own syncs, so memory stays flat however many
        // records held the value.
        ProductModel::query()->whereNotNull('values->'.$this->attribute)->chunkById(200, function (Collection $products) use ($index): void {
            $affected = [];

            foreach ($products as $product) {
                if ($this->purge($product)) {
                    $affected[] = $product->id;
                }
            }

            $index->queue($affected);
        });

        ProductModelModel::query()->whereNotNull('values->'.$this->attribute)->chunkById(200, function (Collection $models) use ($index): void {
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

        if (! isset($values[$this->attribute])) {
            return false;
        }

        if ($this->option === null) {
            unset($values[$this->attribute]);
        } else {
            foreach ($values[$this->attribute] as $channel => $locales) {
                foreach ($locales as $locale => $data) {
                    $kept = is_array($data)
                        ? array_values(array_filter($data, fn (mixed $code): bool => $code !== $this->option))
                        : ($data === $this->option ? null : $data);

                    if ($kept === null || $kept === []) {
                        unset($values[$this->attribute][$channel][$locale]);
                    } else {
                        $values[$this->attribute][$channel][$locale] = $kept;
                    }
                }

                if ($values[$this->attribute][$channel] === []) {
                    unset($values[$this->attribute][$channel]);
                }
            }

            if ($values[$this->attribute] === []) {
                unset($values[$this->attribute]);
            }
        }

        if ($values === $record->ownValues()) {
            return false;
        }

        $record->values = $values;
        $record->save();

        return true;
    }
}
