<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine;
use RefactorCircus\Showroom\Domains\Search\Data\Filter;
use RefactorCircus\Showroom\Domains\Search\Data\ProductQuery;
use RefactorCircus\Showroom\Domains\Search\Data\SearchResults;
use RefactorCircus\Showroom\Domains\Search\Exceptions\UnsupportedSearchException;
use RefactorCircus\Showroom\Domains\Search\Support\SqlFragment;
use RefactorCircus\Showroom\Support\Tables;

/**
 * Searches the products table directly, with no index to keep.
 *
 * A variant's values live on up to three rows — itself, its product model,
 * and that model's parent — so each value condition is checked on all three.
 * Fine for thousands of products; use a search engine beyond that.
 */
final class DatabaseEngine implements SearchEngine
{
    /**
     * The `values` column of each level a value may live on.
     *
     * @return array<int, string>
     */
    private function levels(): array
    {
        return [Tables::name('products').'.values', 'showroom_level1.values', 'showroom_level2.values'];
    }

    public function maintainsIndex(): bool
    {
        return false;
    }

    public function index(iterable $products): void
    {
        //
    }

    public function remove(array $ids): void
    {
        //
    }

    public function reset(): void
    {
        //
    }

    public function search(ProductQuery $query): SearchResults
    {
        $builder = $this->matching($query);

        $total = (clone $builder)->count(Tables::name('products').'.id');

        /** @var array<int, string> $ids */
        $ids = $builder
            ->orderBy(Tables::name('products').'.'.$query->sort, $query->direction)
            ->orderBy(Tables::name('products').'.id')
            ->offset($query->offset())
            ->limit($query->perPage)
            ->pluck(Tables::name('products').'.id')
            ->all();

        return new SearchResults($ids, $total);
    }

    /**
     * Matching products by identifier then id, from just past the given key,
     * without counting them: how an export walks a large catalogue, where
     * every offset page would rescan and recount what came before.
     *
     * @param  array{0: string, 1: string}|null  $after  The identifier and id of the last product already read.
     * @param  int  $offset  Rows to skip first; only to resume from a page number, when no key is known.
     * @return list<array{0: string, 1: string}> Each product's identifier and id, in order.
     */
    public function keyset(ProductQuery $query, ?array $after, int $limit, int $offset = 0): array
    {
        $table = Tables::name('products');
        $direction = $query->direction === 'desc' ? 'desc' : 'asc';
        $builder = $this->matching($query);

        if ($after !== null) {
            [$identifier, $id] = $after;

            $builder->where(fn (Builder $past): Builder => $past
                ->where($table.'.identifier', $direction === 'desc' ? '<' : '>', $identifier)
                ->orWhere(fn (Builder $tie): Builder => $tie->where($table.'.identifier', $identifier)->where($table.'.id', '>', $id)));
        }

        return array_values($builder->toBase()
            ->orderBy($table.'.identifier', $direction)
            ->orderBy($table.'.id')
            ->offset($offset)
            ->limit($limit)
            ->get([$table.'.identifier', $table.'.id'])
            ->map(fn (object $row): array => [(string) data_get($row, 'identifier'), (string) data_get($row, 'id')])
            ->all());
    }

    /**
     * Every product the query's conditions match, unordered.
     *
     * @return Builder<ProductModel>
     */
    private function matching(ProductQuery $query): Builder
    {
        $builder = ProductModel::query()
            ->leftJoin(Tables::name('product_models').' as showroom_level1', 'showroom_level1.id', '=', Tables::name('products').'.parent_id')
            ->leftJoin(Tables::name('product_models').' as showroom_level2', 'showroom_level2.id', '=', 'showroom_level1.parent_id');

        if ($query->search !== null) {
            $term = '%'.$query->search.'%';

            $builder->where(function (Builder $builder) use ($term): void {
                $builder->where(Tables::name('products').'.identifier', 'like', $term);

                foreach ($this->levels() as $column) {
                    $builder->orWhere(new SqlFragment($this->asText($column)), 'like', $term);
                }
            });
        }

        if ($query->family !== null) {
            $family = $query->family;

            $builder->whereHas('family', fn (Builder $families): Builder => $families->where('code', $family));
        }

        if ($query->enabled !== null) {
            $builder->where(Tables::name('products').'.enabled', $query->enabled);
        }

        if ($query->parent !== null) {
            $builder->where(fn (Builder $builder): Builder => $builder
                ->whereIn('showroom_level1.code', [$query->parent])
                ->orWhereIn('showroom_level2.code', [$query->parent]));
        }

        if ($query->owner !== null) {
            $owner = OwnerModel::query()->where('code', $query->owner)->first();
            $owners = $owner === null ? [] : OwnerModel::query()->subtreeOf($owner)->pluck('id')->all();

            $builder->where(fn (Builder $builder): Builder => $builder
                ->whereIn(Tables::name('products').'.owner_id', $owners)
                ->orWhereIn('showroom_level1.owner_id', $owners)
                ->orWhereIn('showroom_level2.owner_id', $owners));
        }

        if ($query->category !== null) {
            $category = CategoryModel::query()->where('code', $query->category)->first();
            $categories = $category === null ? [] : CategoryModel::query()->subtreeOf($category)->pluck('id')->all();

            $builder->where(function (Builder $builder) use ($categories): void {
                $builder->whereExists(fn (QueryBuilder $sub) => $sub->from(Tables::name('category_product'))
                    ->whereColumn(Tables::name('category_product').'.product_id', Tables::name('products').'.id')
                    ->whereIn(Tables::name('category_product').'.category_id', $categories));

                foreach (['showroom_level1', 'showroom_level2'] as $level) {
                    $builder->orWhereExists(fn (QueryBuilder $sub) => $sub->from(Tables::name('category_product_model'))
                        ->whereColumn(Tables::name('category_product_model').'.product_model_id', $level.'.id')
                        ->whereIn(Tables::name('category_product_model').'.category_id', $categories));
                }
            });
        }

        if ($query->status !== null) {
            $builder->where(Tables::name('products').'.status', $query->status);
        }

        if ($query->published !== null) {
            $query->published
                ? $builder->whereNotNull(Tables::name('products').'.published_version')
                : $builder->whereNull(Tables::name('products').'.published_version');
        }

        if ($query->updatedSince !== null) {
            $builder->where(Tables::name('products').'.changed_at', '>=', $query->updatedSince);
        }

        if ($query->complete !== null) {
            $complete = $query->complete;

            foreach ($complete->locales() ?: ['-'] as $locale) {
                $builder->whereExists(fn (QueryBuilder $sub) => $sub->from(Tables::name('product_completeness'))
                    ->join(Tables::name('channels'), Tables::name('channels').'.id', '=', Tables::name('product_completeness').'.channel_id')
                    ->join(Tables::name('locales'), Tables::name('locales').'.id', '=', Tables::name('product_completeness').'.locale_id')
                    ->whereColumn(Tables::name('product_completeness').'.product_id', Tables::name('products').'.id')
                    ->where(Tables::name('channels').'.code', $complete->scope)
                    ->where(Tables::name('locales').'.code', $locale)
                    ->where(Tables::name('product_completeness').'.ratio', '>=', $complete->min));
            }
        }

        $types = $this->types($query->filters);

        foreach ($query->filters as $filter) {
            $this->applyFilter($builder, $filter, $types[$filter->attribute] ?? AttributeType::Text);
        }

        return $builder;
    }

    /**
     * @param  Builder<ProductModel>  $builder
     */
    private function applyFilter(Builder $builder, Filter $filter, AttributeType $type): void
    {
        $path = implode('->', [$filter->attribute, $filter->scope ?? Values::ALL_CHANNELS, $filter->locale ?? Values::ALL_LOCALES]);

        if ($type === AttributeType::Metric) {
            $path .= '->amount';
        }

        if ($type === AttributeType::Price) {
            throw UnsupportedSearchException::filter('database', $filter->attribute, $filter->operator);
        }

        $match = match ($filter->operator) {
            '=', '!=' => fn (QueryBuilder $level, string $column) => $this->equals($level, $column.'->'.$path, $type, $filter->value),
            'in', 'not_in' => fn (QueryBuilder $level, string $column) => $level->where(function (QueryBuilder $any) use ($column, $path, $type, $filter): void {
                foreach ($filter->values() as $value) {
                    $any->orWhere(fn (QueryBuilder $one) => $this->equals($one, $column.'->'.$path, $type, $value));
                }
            }),
            '>', '>=', '<', '<=' => fn (QueryBuilder $level, string $column) => $level->where(
                new SqlFragment($this->asNumber($column.'->'.$path)),
                $filter->operator,
                $filter->value,
            ),
            'empty', 'not_empty' => fn (QueryBuilder $level, string $column) => $level->whereNotNull($column.'->'.$path),
            default => throw UnsupportedSearchException::filter('database', $filter->attribute, $filter->operator),
        };

        // A value sits on exactly one level, so "any level matches" is exact,
        // and each level's clause is false, never null, when the value is
        // absent — which keeps the negated forms honest.
        $anyLevel = function (QueryBuilder $query) use ($match): void {
            foreach ($this->levels() as $column) {
                $query->orWhere(fn (QueryBuilder $level) => $match($level->whereNotNull($column), $column));
            }
        };

        in_array($filter->operator, ['!=', 'not_in', 'empty'], true)
            ? $builder->getQuery()->whereNot($anyLevel)
            : $builder->getQuery()->where($anyLevel);
    }

    private function equals(QueryBuilder $query, string $path, AttributeType $type, mixed $value): QueryBuilder
    {
        if ($type === AttributeType::Multiselect) {
            return $query->whereJsonContains($path, $value);
        }

        if ($type === AttributeType::Boolean) {
            return $query->where($path, filter_var($value, FILTER_VALIDATE_BOOLEAN));
        }

        return $query->whereNotNull($path)->where($path, is_bool($value) ? $value : (string) $value);
    }

    /**
     * @param  array<int, Filter>  $filters
     * @return array<string, AttributeType>
     */
    private function types(array $filters): array
    {
        if ($filters === []) {
            return [];
        }

        return AttributeModel::query()
            ->whereIn('code', array_map(fn (Filter $filter): string => $filter->attribute, $filters))
            ->get(['code', 'type'])
            ->mapWithKeys(fn (AttributeModel $attribute): array => [$attribute->code => $attribute->type])
            ->all();
    }

    private function asText(string $column): string
    {
        $wrapped = $this->grammar()->wrap($column);

        return $this->driver() === 'pgsql' ? 'CAST('.$wrapped.' AS TEXT)' : $wrapped;
    }

    private function asNumber(string $path): string
    {
        $wrapped = $this->grammar()->wrap($path);

        return match ($this->driver()) {
            'sqlite' => 'CAST('.$wrapped.' AS REAL)',
            'pgsql' => 'CAST('.$wrapped.' AS NUMERIC)',
            default => 'CAST('.$wrapped.' AS DECIMAL(30,10))',
        };
    }

    private function grammar(): Grammar
    {
        return ProductModel::query()->getQuery()->getGrammar();
    }

    private function driver(): string
    {
        return (new ProductModel)->getConnection()->getDriverName();
    }
}
