<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex\Webhooks;

use Illuminate\Database\ConnectionInterface;
use RefactorCircus\Impex\Domains\Subscription\Contracts\SubscriptionMatcher;
use RefactorCircus\Impex\Domains\Subscription\Models\SubscriptionModel;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Matches products to filtered subscriptions.
 *
 * A filter names category, owner, family and model codes. Each key narrows
 * (a product must satisfy every key given) and each code widens (any one of
 * a key's codes will do). A category or owner also covers everything beneath
 * it, through the materialized path both trees keep.
 *
 * Built for many subscriptions over large chunks: the filters become an
 * index keyed by path and code once per call, and each product looks up only
 * its own paths' prefixes in it — never a scan of every subscription.
 */
final class ProductScopeMatcher implements SubscriptionMatcher
{
    public function __construct(private readonly ConnectionInterface $db) {}

    public function match(array $snapshots, array $subscriptions): array
    {
        [$filters, $index] = $this->index($subscriptions);
        $matches = [];

        foreach ($snapshots as $key => $snapshot) {
            /** @var array{categories?: list<string>, owner?: string|null, family?: string|null, models?: list<string>} $scope */
            $scope = is_array($snapshot['_scope'] ?? null) ? $snapshot['_scope'] : [];
            $candidates = [];

            foreach ($scope['categories'] ?? [] as $path) {
                foreach ($this->prefixes($path) as $prefix) {
                    $candidates += $index['categories'][$prefix] ?? [];
                }
            }

            foreach ($this->prefixes($scope['owner'] ?? null) as $prefix) {
                $candidates += $index['owners'][$prefix] ?? [];
            }

            $candidates += $index['families'][$scope['family'] ?? ''] ?? [];

            foreach ($scope['models'] ?? [] as $model) {
                $candidates += $index['models'][$model] ?? [];
            }

            foreach (array_keys($candidates) as $id) {
                if ($this->satisfies($scope, $filters[$id])) {
                    $matches[$id][] = (string) $key;
                }
            }
        }

        return $matches;
    }

    /**
     * Each subscription's filter with codes resolved to paths, and an index
     * from path or code to the subscriptions that name it.
     *
     * @param  list<SubscriptionModel>  $subscriptions
     * @return array{0: array<string, array<string, list<string>>>, 1: array<string, array<string, array<string, true>>>}
     */
    private function index(array $subscriptions): array
    {
        $codes = ['categories' => [], 'owners' => []];

        foreach ($subscriptions as $subscription) {
            foreach (['categories', 'owners'] as $dimension) {
                foreach ($this->codes($subscription, $dimension) as $code) {
                    $codes[$dimension][$code] = true;
                }
            }
        }

        $paths = [
            'categories' => $this->paths(Tables::name('categories'), array_keys($codes['categories'])),
            'owners' => $this->paths(Tables::name('owners'), array_keys($codes['owners'])),
        ];

        $filters = [];
        $index = ['categories' => [], 'owners' => [], 'families' => [], 'models' => []];

        foreach ($subscriptions as $subscription) {
            $filter = [];

            foreach (['categories', 'owners'] as $dimension) {
                if ($this->codes($subscription, $dimension) === []) {
                    continue;
                }

                // A code that no longer exists matches nothing, rather than
                // dropping the key and matching everything.
                $filter[$dimension] = array_values(array_filter(array_map(
                    fn (string $code): ?string => $paths[$dimension][$code] ?? null,
                    $this->codes($subscription, $dimension),
                )));

                foreach ($filter[$dimension] as $path) {
                    $index[$dimension][$path][$subscription->id] = true;
                }
            }

            foreach (['families', 'models'] as $dimension) {
                $named = $this->codes($subscription, $dimension);

                if ($named === []) {
                    continue;
                }

                $filter[$dimension] = $named;

                foreach ($named as $code) {
                    $index[$dimension][$code][$subscription->id] = true;
                }
            }

            $filters[$subscription->id] = $filter;
        }

        return [$filters, $index];
    }

    /**
     * @param  array{categories?: list<string>, owner?: string|null, family?: string|null, models?: list<string>}  $scope
     * @param  array<string, list<string>>  $filter
     */
    private function satisfies(array $scope, array $filter): bool
    {
        if (isset($filter['categories']) && ! $this->beneath($scope['categories'] ?? [], $filter['categories'])) {
            return false;
        }

        if (isset($filter['owners']) && ! $this->beneath(array_filter([$scope['owner'] ?? null]), $filter['owners'])) {
            return false;
        }

        if (isset($filter['families']) && ! in_array($scope['family'] ?? null, $filter['families'], true)) {
            return false;
        }

        return ! isset($filter['models']) || array_intersect($scope['models'] ?? [], $filter['models']) !== [];
    }

    /**
     * Whether any of the paths is at or beneath any of the roots.
     *
     * @param  array<int, string>  $paths
     * @param  list<string>  $roots
     */
    private function beneath(array $paths, array $roots): bool
    {
        foreach ($paths as $path) {
            foreach ($roots as $root) {
                if (str_starts_with($path, $root)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * "/a/b/c/" → "/a/", "/a/b/", "/a/b/c/".
     *
     * @return list<string>
     */
    private function prefixes(?string $path): array
    {
        if ($path === null || $path === '') {
            return [];
        }

        $prefixes = [];
        $prefix = '/';

        foreach (array_filter(explode('/', $path), fn (string $part): bool => $part !== '') as $part) {
            $prefix .= $part.'/';
            $prefixes[] = $prefix;
        }

        return $prefixes;
    }

    /**
     * @return list<string>
     */
    private function codes(SubscriptionModel $subscription, string $dimension): array
    {
        $codes = $subscription->filter[$dimension] ?? [];

        return is_array($codes) ? array_values(array_map('strval', $codes)) : [];
    }

    /**
     * @param  list<string>  $codes
     * @return array<string, string>
     */
    private function paths(string $table, array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        /** @var array<string, string> $paths */
        $paths = $this->db->table($table)->whereIn('code', $codes)->pluck('path', 'code')->all();

        return $paths;
    }
}
