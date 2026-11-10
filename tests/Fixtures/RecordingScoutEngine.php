<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

/**
 * A Scout engine that records what it is asked and answers from memory.
 */
final class RecordingScoutEngine extends Engine
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public array $documents = [];

    /**
     * @var array<int, Builder<Model>>
     */
    public array $searches = [];

    public int $flushes = 0;

    public function update($models): void
    {
        foreach ($models as $model) {
            $this->documents[(string) $model->getScoutKey()] = $model->toSearchableArray();
        }
    }

    public function delete($models): void
    {
        foreach ($models as $model) {
            unset($this->documents[(string) $model->getScoutKey()]);
        }
    }

    public function search(Builder $builder): mixed
    {
        return $this->paginate($builder, 25, 1);
    }

    public function paginate(Builder $builder, $perPage, $page): mixed
    {
        $this->searches[] = $builder;

        $hits = collect($this->documents)->filter(function (array $document) use ($builder): bool {
            foreach ($builder->wheres as $where) {
                $actual = data_get($document, $where['field']);

                // A list field matches when it holds the value, as Meilisearch,
                // Typesense and Algolia match an array attribute.
                $matches = match ($where['operator']) {
                    '=' => is_array($actual) ? in_array($where['value'], $actual, true) : $actual === $where['value'],
                    '!=' => $actual !== $where['value'],
                    '>' => $actual > $where['value'],
                    '>=' => $actual >= $where['value'],
                    '<' => $actual < $where['value'],
                    default => $actual <= $where['value'],
                };

                if (! $matches) {
                    return false;
                }
            }

            foreach ($builder->whereIns as $field => $values) {
                if (! in_array(data_get($document, $field), $values, true)) {
                    return false;
                }
            }

            return true;
        });

        return ['ids' => $hits->keys()->forPage($page, $perPage)->values()->all(), 'total' => $hits->count()];
    }

    public function mapIds($results): Collection
    {
        return collect($results['ids']);
    }

    public function map(Builder $builder, $results, $model): Collection
    {
        return collect();
    }

    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        return LazyCollection::make();
    }

    public function getTotalCount($results): int
    {
        return $results['total'];
    }

    public function flush($model): void
    {
        $this->flushes++;
        $this->documents = [];
    }

    public function createIndex($name, array $options = []): mixed
    {
        return null;
    }

    public function deleteIndex($name): mixed
    {
        return null;
    }
}
