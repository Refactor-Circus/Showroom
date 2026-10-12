<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Services;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Events\ProductsQueuedForSync;
use RefactorCircus\Showroom\Domains\Search\Support\SyncBuffer;
use RefactorCircus\Showroom\Domains\Workflow\Services\CompletenessCalculator;
use RefactorCircus\Showroom\Jobs\QueueProductSyncs;
use RefactorCircus\Showroom\Jobs\SyncProductIndex;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Keeps what is derived from products — completeness scores and the search
 * engine's index — in step with the products table.
 */
final class ProductIndex
{
    public function __construct(
        private readonly SearchEngine $engine,
        private readonly Config $config,
        private readonly CompletenessCalculator $completeness,
        private readonly Events $events,
        private readonly SyncBuffer $buffer,
    ) {}

    /**
     * Run a callback with every sync it queues held back, then queue them
     * together: a bulk write — many rows, one process — sends one job per
     * 500 products instead of one per write. Nested calls join the outer one.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function deferring(callable $callback): mixed
    {
        if (! $this->buffer->open()) {
            return $callback();
        }

        try {
            return $callback();
        } finally {
            // What was written before a failure still needs its sync; a
            // rolled-back transaction drops the jobs with it.
            $this->queue($this->buffer->close());
        }
    }

    /**
     * Queue a sync of these products, after the surrounding transaction
     * commits.
     *
     * @param  array<int, string>  $ids
     */
    public function queue(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $ids = array_values(array_unique($ids));

        if ($this->buffer->isOpen()) {
            $this->buffer->add($ids);

            return;
        }

        $this->events->dispatch(new ProductsQueuedForSync($ids));

        foreach (array_chunk($ids, 500) as $chunk) {
            SyncProductIndex::dispatch($chunk)
                ->onConnection($this->config->get('showroom.search.queue.connection'))
                ->onQueue($this->config->get('showroom.search.queue.queue'))
                ->afterCommit();
        }
    }

    /**
     * Queue a sync of every product a query matches, in chunks.
     *
     * @param  Builder<ProductModel>  $query
     */
    public function queueQuery(Builder $query): void
    {
        $query->select('id')->chunkById(500, function (Collection $products): void {
            /** @var array<int, string> $ids */
            $ids = $products->modelKeys();

            $this->queue($ids);
        });
    }

    /**
     * Queue a job that lists a scope's products and queues their syncs,
     * after the surrounding transaction commits.
     */
    public function queueWithin(SyncScope $scope, ?string $id = null): void
    {
        QueueProductSyncs::dispatch($scope, $id)
            ->onConnection($this->config->get('showroom.search.queue.connection'))
            ->onQueue($this->config->get('showroom.search.queue.queue'))
            ->afterCommit();
    }

    /**
     * The products of a scope, as a query to walk in chunks; null when the
     * family, owner or category it names is gone.
     *
     * @return Builder<ProductModel>|null
     */
    public function productsWithin(SyncScope $scope, ?string $id = null): ?Builder
    {
        return match ($scope) {
            SyncScope::All => ProductModel::query(),
            SyncScope::Family => ProductModel::query()->where('family_id', $id),
            SyncScope::Owner => $this->ownedWithin(OwnerModel::query()->find($id)),
            SyncScope::Category => $this->inCategories(CategoryModel::query()->find($id)),
        };
    }

    /**
     * Write these products to the index, and remove those that no longer exist.
     *
     * @param  array<int, string>  $ids
     */
    public function sync(array $ids): void
    {
        // Every change a product shows passes through here — its own edits
        // and whatever it inherits — so this is where "changed since" is
        // kept. A bare update: no events, and updated_at stays the row's own.
        ProductModel::query()->whereKey($ids)->toBase()->update(['changed_at' => now()]);

        $products = $this->withContext(ProductModel::query()->whereKey($ids))->get();

        // Completeness first: the index carries it.
        $this->completeness->refresh($products);

        if (! $this->engine->maintainsIndex()) {
            return;
        }

        $this->engine->index($products->load('completeness.channel', 'completeness.locale'));

        $missing = array_values(array_diff($ids, $products->modelKeys()));

        if ($missing !== []) {
            $this->engine->remove($missing);
        }
    }

    /**
     * Empty the index and write every product to it again.
     *
     * @param  (callable(int): void)|null  $progress  Called with each chunk's size.
     */
    public function rebuild(?callable $progress = null): int
    {
        $indexed = $this->engine->maintainsIndex();

        if ($indexed) {
            $this->engine->reset();
        }

        $count = 0;

        $this->withContext(ProductModel::query())->chunkById(500, function (Collection $products) use (&$count, $progress, $indexed): void {
            $this->completeness->refresh($products);

            if ($indexed) {
                $this->engine->index($products->load('completeness.channel', 'completeness.locale'));
            }

            $count += $products->count();

            if ($progress !== null) {
                $progress($products->count());
            }
        });

        return $count;
    }

    /**
     * The ids of every variant product under a product model, at any depth.
     *
     * @return array<int, string>
     */
    public function productIdsUnder(ProductModelModel $model): array
    {
        $models = [$model->id, ...ProductModelModel::query()->where('parent_id', $model->id)->pluck('id')->all()];

        /** @var array<int, string> $ids */
        $ids = ProductModel::query()->whereIn('parent_id', $models)->pluck('id')->all();

        return $ids;
    }

    /**
     * The ids of every product owned by this owner or anything beneath it,
     * directly or through a product model.
     *
     * @return array<int, string>
     */
    public function productIdsOwnedWithin(OwnerModel $owner): array
    {
        /** @var array<int, string> $ids */
        $ids = $this->ownedWithin($owner)?->pluck('id')->all() ?? [];

        return $ids;
    }

    /**
     * The ids of every product in this category or any category beneath it,
     * directly or through a product model.
     *
     * @return array<int, string>
     */
    public function productIdsInCategories(CategoryModel $category): array
    {
        /** @var array<int, string> $ids */
        $ids = $this->inCategories($category)?->pluck('id')->all() ?? [];

        return $ids;
    }

    /**
     * Products owned by this owner or anything beneath it, directly or
     * through a product model, without loading the ids first.
     *
     * @return Builder<ProductModel>|null
     */
    private function ownedWithin(?OwnerModel $owner): ?Builder
    {
        if ($owner === null) {
            return null;
        }

        $owners = fn (): Builder => OwnerModel::query()->subtreeOf($owner)->select('id');
        $roots = ProductModelModel::query()->whereIn('owner_id', $owners())->select('id');
        $models = ProductModelModel::query()
            ->where(fn (Builder $query): Builder => $query->whereIn('owner_id', $owners())->orWhereIn('parent_id', $roots))
            ->select('id');

        return ProductModel::query()->where(fn (Builder $query): Builder => $query
            ->whereIn('owner_id', $owners())
            ->orWhereIn('parent_id', $models));
    }

    /**
     * Products in this category or any category beneath it, directly or
     * through a product model, without loading the ids first.
     *
     * @return Builder<ProductModel>|null
     */
    private function inCategories(?CategoryModel $category): ?Builder
    {
        if ($category === null) {
            return null;
        }

        $categories = fn (): Builder => CategoryModel::query()->subtreeOf($category)->select('id');
        $filed = fn (Builder $query): Builder => $query->whereIn(Tables::name('categories').'.id', $categories());
        $roots = ProductModelModel::query()->whereHas('categories', $filed)->select('id');
        $models = ProductModelModel::query()
            ->where(fn (Builder $query): Builder => $query->whereHas('categories', $filed)->orWhereIn('parent_id', $roots))
            ->select('id');

        return ProductModel::query()->where(fn (Builder $query): Builder => $query
            ->whereHas('categories', $filed)
            ->orWhereIn('parent_id', $models));
    }

    /**
     * @param  Builder<ProductModel>  $query
     * @return Builder<ProductModel>
     */
    private function withContext(Builder $query): Builder
    {
        return $query->with(['family', 'owner', 'categories', 'parent.owner', 'parent.categories', 'parent.parent.owner', 'parent.parent.categories']);
    }
}
