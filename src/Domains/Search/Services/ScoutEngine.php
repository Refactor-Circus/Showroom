<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Services;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine;
use RefactorCircus\Showroom\Domains\Search\Data\Filter;
use RefactorCircus\Showroom\Domains\Search\Data\ProductQuery;
use RefactorCircus\Showroom\Domains\Search\Data\SearchResults;
use RefactorCircus\Showroom\Domains\Search\Exceptions\UnsupportedSearchException;
use RefactorCircus\Showroom\Domains\Search\Models\SearchableProductModel;

/**
 * Searches through whichever Laravel Scout engine the application configures
 * (`scout.driver`): Meilisearch, Typesense, Algolia, ...
 *
 * Comparisons (`=`, `!=`, ranges) and `in`/`not_in` pass through Scout's
 * builder, so each works where the chosen engine supports it; `empty`,
 * `not_empty` and price filters are refused. Filterable attributes must be
 * declared in the engine's own index settings, as Scout expects.
 */
final class ScoutEngine implements SearchEngine
{
    public function __construct(private readonly EngineManager $engines) {}

    public function maintainsIndex(): bool
    {
        return true;
    }

    public function index(iterable $products): void
    {
        /** @var Collection<int, SearchableProductModel> $models */
        $models = new Collection;

        foreach (ProductDocument::many($products) as $document) {
            $models->push(SearchableProductModel::fromDocument($document));
        }

        if ($models->isNotEmpty()) {
            $this->engine()->update($models);
        }
    }

    public function remove(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $this->engine()->delete(new Collection(array_map(
            fn (string $id): SearchableProductModel => SearchableProductModel::fromDocument(['id' => $id]),
            $ids,
        )));
    }

    public function reset(): void
    {
        $this->engine()->flush(new SearchableProductModel);
    }

    public function search(ProductQuery $query): SearchResults
    {
        /** @var Builder<SearchableProductModel> $builder */
        $builder = new Builder(new SearchableProductModel, $query->search ?? '');

        // `owners` holds the whole chain; engines match a value in a list.
        foreach (['family' => $query->family, 'enabled' => $query->enabled, 'parent' => $query->parent, 'owners' => $query->owner, 'category_tree' => $query->category, 'status' => $query->status, 'published' => $query->published] as $field => $value) {
            if ($value !== null) {
                $builder->where($field, $value);
            }
        }

        $metrics = AttributeModel::query()
            ->whereIn('code', array_map(fn (Filter $filter): string => $filter->attribute, $query->filters))
            ->whereIn('type', [AttributeType::Metric, AttributeType::Price])
            ->pluck('type', 'code')
            ->all();

        foreach ($query->filters as $filter) {
            $this->applyFilter($builder, $filter, $metrics[$filter->attribute] ?? null);
        }

        if ($query->complete !== null) {
            foreach ($query->complete->locales() ?: ['-'] as $locale) {
                $builder->where('completeness.'.$query->complete->scope.'.'.$locale, '>=', $query->complete->min);
            }
        }

        if ($query->updatedSince !== null) {
            $builder->where('changed_at', '>=', $query->updatedSince->toIso8601String());
        }

        $builder->orderBy($query->sort, $query->direction);

        $engine = $this->engine();
        $results = $engine->paginate($builder, $query->perPage, $query->page);

        /** @var array<int, string> $ids */
        $ids = $engine->mapIds($results)->map(fn (mixed $id): string => (string) $id)->values()->all();

        return new SearchResults($ids, (int) $engine->getTotalCount($results));
    }

    /**
     * @param  Builder<SearchableProductModel>  $builder
     */
    private function applyFilter(Builder $builder, Filter $filter, ?AttributeType $type): void
    {
        if ($type === AttributeType::Price) {
            throw UnsupportedSearchException::filter('scout', $filter->attribute, $filter->operator);
        }

        $field = implode('.', ['values', $filter->attribute, $filter->scope ?? Values::ALL_CHANNELS, $filter->locale ?? Values::ALL_LOCALES]);

        if ($type === AttributeType::Metric) {
            $field .= '.amount';
        }

        $value = is_string($filter->value) && is_numeric($filter->value) && in_array($filter->operator, ['>', '>=', '<', '<='], true)
            ? $filter->value + 0
            : $filter->value;

        match ($filter->operator) {
            '=', '!=', '>', '>=', '<', '<=' => $builder->where($field, $filter->operator, $value),
            'in' => $builder->whereIn($field, $filter->values()),
            'not_in' => $builder->whereNotIn($field, $filter->values()),
            default => throw UnsupportedSearchException::filter('scout', $filter->attribute, $filter->operator),
        };
    }

    private function engine(): Engine
    {
        return $this->engines->engine();
    }
}
