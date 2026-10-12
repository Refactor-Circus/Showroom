<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Webhooks;

use Illuminate\Support\Facades\Route;
use RefactorCircus\Impex\Domains\Subscription\Contracts\Formatter;
use RefactorCircus\Impex\Domains\Subscription\Contracts\Stream;
use RefactorCircus\Impex\Domains\Subscription\Data\StreamEvent;
use RefactorCircus\Impex\Domains\Subscription\Enums\EventKind;
use RefactorCircus\Impex\Domains\Subscription\Models\SubscriptionModel;
use RefactorCircus\Impex\Domains\Subscription\Support\TopicMask;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Attribute\Data\ValueFilter;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;

/**
 * Products as a subscriber receives them.
 *
 * - thin: which product changed, in which topics, and where to fetch it
 * - slice: only the topics that changed and that the subscriber follows
 * - full: the whole published product
 *
 * Values are narrowed to the subscription's channel and locales (its
 * `options.channel` and `options.locales`), in the API's standard shape.
 * Assets carry a URL and checksum, never the file and never its storage
 * path.
 */
final class ProductFormatter implements Formatter
{
    public function __construct(
        private readonly string $mode,
        private readonly TopicMap $topics,
    ) {}

    public function format(Stream $stream, SubscriptionModel $subscription, array $events, array $snapshots): array
    {
        return array_map(function (StreamEvent $event) use ($stream, $subscription, $snapshots): array {
            $entry = array_filter([
                'id' => (string) $event->id,
                'type' => $event->kind->value,
                'reason' => $event->name,
                'identifier' => $event->subjectKey,
                'topics' => TopicMask::names($stream->topics(), $event->topics & $subscription->topics),
                'occurred_at' => $event->occurredAt->toIso8601String(),
            ], fn (mixed $value): bool => $value !== null);

            $snapshot = $snapshots[$event->subjectKey] ?? null;

            if ($event->kind === EventKind::Removed || $snapshot === null) {
                return $entry;
            }

            $entry['version'] = $snapshot['version'] ?? null;

            return match ($this->mode) {
                'thin' => [...$entry, 'href' => $this->href($event->subjectKey)],
                'full' => [...$entry, 'data' => $this->present($snapshot, $subscription)],
                default => [...$entry, 'data' => $this->slices($snapshot, $subscription, $entry['topics'])],
            };
        }, $events);
    }

    /**
     * The whole product, as a subscriber may see it.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function present(array $snapshot, SubscriptionModel $subscription): array
    {
        unset($snapshot['_scope']);

        /** @var array<string, array<string, array<string, mixed>>> $values */
        $values = is_array($snapshot['values'] ?? null) ? $snapshot['values'] : [];
        $snapshot['values'] = (object) $this->values($values, $subscription);
        $snapshot['assets'] = $this->assets(is_array($snapshot['assets'] ?? null) ? $snapshot['assets'] : []);

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<int, string>  $topics
     * @return array<string, mixed>
     */
    private function slices(array $snapshot, SubscriptionModel $subscription, array $topics): array
    {
        $slices = array_intersect_key($this->topics->slice($snapshot), array_flip($topics));

        foreach ($slices as $topic => $slice) {
            if ($slice === null) {
                continue;
            }

            if (isset($slice['values']) && is_array($slice['values'])) {
                /** @var array<string, array<string, array<string, mixed>>> $values */
                $values = $slice['values'];
                $slice['values'] = (object) $this->values($values, $subscription);
            }

            if (isset($slice['assets']) && is_array($slice['assets'])) {
                $slice['assets'] = $this->assets($slice['assets']);
            }

            $slices[$topic] = $slice;
        }

        return $slices;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $values
     * @return array<string, mixed>
     */
    private function values(array $values, SubscriptionModel $subscription): array
    {
        // Subscription options are stored as the subscriber set them.
        $channel = $subscription->option('channel');
        $locales = $subscription->option('locales');

        $filter = new ValueFilter(
            scope: is_string($channel) ? $channel : null,
            locales: is_array($locales) ? array_values(array_filter($locales, is_string(...))) : null,
        );

        return Values::toStandard($filter->apply($values));
    }

    /**
     * @param  array<int, mixed>  $assets
     * @return list<array<string, mixed>>
     */
    private function assets(array $assets): array
    {
        $presented = [];

        foreach ($assets as $asset) {
            if (! is_array($asset)) {
                continue;
            }

            $model = (new AssetModel)->forceFill([
                'disk' => $asset['disk'] ?? null,
                'path' => $asset['path'] ?? null,
                'filename' => $asset['filename'] ?? null,
            ]);

            unset($asset['disk'], $asset['path']);

            $presented[] = [...$asset, 'url' => $model->url()];
        }

        return $presented;
    }

    private function href(string $identifier): ?string
    {
        return Route::has('showroom.products.show') ? route('showroom.products.show', $identifier) : null;
    }
}
