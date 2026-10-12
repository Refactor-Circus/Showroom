<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Webhooks;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher as Events;
use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetAttachedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetDetachedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;
use RefactorCircus\Showroom\Domains\Product\Events\ProductDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelDeletedActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Events\ProductModelDeletingActionEvent;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Events\ProductsQueuedForSync;
use RefactorCircus\Showroom\Support\Tables;
use stdClass;

/**
 * Tells Impex which products may have changed, by identifier. That is all it
 * does: one upsert per batch, inside the write's own transaction. Whether
 * anything a subscriber sees changed, and who that is, is worked out later,
 * on the queue.
 *
 * Every product write already queues an index sync, which covers edits,
 * transitions, and changes products inherit from models, families,
 * categories, owners and channels. Deletions are caught before the rows go,
 * while their identifiers can still be read; asset links, which bypass the
 * product, are caught from their own events.
 */
final class CaptureProductChanges
{
    /**
     * Identifiers of a model's variants, read as the model starts to be
     * deleted and touched once it has been.
     *
     * @var array<string, list<string>>
     */
    private array $doomed = [];

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Config $config,
    ) {}

    public function subscribe(Events $events): void
    {
        $events->listen(ProductsQueuedForSync::class, fn (ProductsQueuedForSync $event) => $this->touchIds($event->ids));
        // A deleted product is touched once it is gone, so detection finds it
        // missing; a model's variants are read before they go, while they
        // still can be.
        $events->listen(ProductDeletedActionEvent::class, fn (ProductDeletedActionEvent $event) => $this->touch([$event->product->identifier]));
        $events->listen(ProductModelDeletingActionEvent::class, function (ProductModelDeletingActionEvent $event): void {
            $this->doomed[$event->productModel->id] = $this->identifiers($this->underModel($event->productModel->id));
        });
        $events->listen(ProductModelDeletedActionEvent::class, function (ProductModelDeletedActionEvent $event): void {
            $this->touch($this->doomed[$event->productModel->id] ?? []);
            unset($this->doomed[$event->productModel->id]);
        });
        $events->listen(AssetAttachedActionEvent::class, fn (AssetAttachedActionEvent $event) => $this->touchLinked($event->data->type, $event->data->target));
        $events->listen(AssetDetachedActionEvent::class, fn (AssetDetachedActionEvent $event) => $this->touchLinked($event->data->type, $event->data->target));
        $events->listen(AssetUpdatedActionEvent::class, fn (AssetUpdatedActionEvent $event) => $this->touchUsing($event->asset));
        // Before the links go with it.
        $events->listen(AssetDeletingActionEvent::class, fn (AssetDeletingActionEvent $event) => $this->touchUsing($event->asset));
    }

    /**
     * @param  array<int, string>  $ids
     */
    private function touchIds(array $ids): void
    {
        $this->touch($this->identifiers($ids));
    }

    /**
     * @param  array<int, string>  $ids
     * @return list<string>
     */
    private function identifiers(array $ids): array
    {
        $identifiers = [];

        foreach (array_chunk(array_values(array_unique($ids)), 1000) as $chunk) {
            foreach ($this->db->table(Tables::name('products'))->whereIn('id', $chunk)->pluck('identifier') as $identifier) {
                $identifiers[] = (string) $identifier;
            }
        }

        return $identifiers;
    }

    /**
     * @param  list<string>  $identifiers
     */
    private function touch(array $identifiers): void
    {
        if ($identifiers === []) {
            return;
        }

        app(Impex::class)->streams()->touch($this->stream(), $identifiers);
    }

    /**
     * The product, or every product under the model, that an attach or
     * detach targeted.
     */
    private function touchLinked(string $type, string $target): void
    {
        if (! in_array($type, ['product', 'product_model'], true)) {
            return;
        }

        $record = app(AssetLinks::class)->resolve($type, $target);

        if ($record instanceof ProductModel) {
            $this->touch([$record->identifier]);
        } elseif ($record instanceof ProductModelModel) {
            $this->touchIds($this->underModel($record->id));
        }
    }

    /**
     * Every product showing the asset, directly or through a model, when its
     * file is replaced.
     */
    private function touchUsing(AssetModel $asset): void
    {
        $links = $this->db->table(Tables::name('asset_links'))->where('asset_id', $asset->id)->get(['linkable_type', 'linkable_id']);
        $productMorph = (new ProductModel)->getMorphClass();
        $modelMorph = (new ProductModelModel)->getMorphClass();
        $ids = [];

        foreach ($links as $link) {
            /** @var stdClass $link */
            if ($link->linkable_type === $productMorph) {
                $ids[] = (string) $link->linkable_id;
            } elseif ($link->linkable_type === $modelMorph) {
                $ids = [...$ids, ...$this->underModel((string) $link->linkable_id)];
            }
        }

        $this->touchIds($ids);
    }

    /**
     * @return array<int, string>
     */
    private function underModel(string $modelId): array
    {
        $models = [$modelId, ...$this->db->table(Tables::name('product_models'))->where('parent_id', $modelId)->pluck('id')->map(fn (mixed $id): string => (string) $id)->all()];

        return $this->db->table(Tables::name('products'))->whereIn('parent_id', $models)->pluck('id')->map(fn (mixed $id): string => (string) $id)->all();
    }

    private function stream(): string
    {
        $stream = $this->config->get('showroom.impex.webhooks.stream', 'showroom.products');

        return is_string($stream) ? $stream : 'showroom.products';
    }
}
