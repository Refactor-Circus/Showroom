<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Domains\Attribute\Data\ValueFilter;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Search\Data\Complete;
use RefactorCircus\Showroom\Domains\Search\Data\Filter;
use RefactorCircus\Showroom\Domains\Search\Data\ProductQuery;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListProductsAction: the search, its filters, facets, order and
 * page, and optionally only one channel's (`scope`) and some locales' values.
 */
final readonly class ListProductsData extends Data
{
    /**
     * @param  list<Filter>  $filters
     * @param  list<string>  $facets
     * @param  list<string>|null  $locales
     */
    public function __construct(
        public ?string $search = null,
        public ?string $family = null,
        public ?bool $enabled = null,
        public ?string $parent = null,
        public ?string $owner = null,
        public ?string $category = null,
        public ?ProductStatus $status = null,
        public ?bool $published = null,
        public ?string $updatedSince = null,
        public ?Complete $complete = null,
        #[ListOf(Filter::class)]
        public array $filters = [],
        public array $facets = [],
        public ?string $sort = null,
        public ?string $scope = null,
        public ?array $locales = null,
        public ?int $page = null,
        public ?int $perPage = null,
    ) {}

    /**
     * How the search pages: numbered, with a total. Its tool states the same
     * limits.
     */
    public static function pagination(): Pagination
    {
        return Paging::pages();
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $sortable = [];

        foreach (ProductQuery::SORTABLE as $field) {
            $sortable[] = $field;
            $sortable[] = '-'.$field;
        }

        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'family' => ['sometimes', 'nullable', 'string', 'max:100'],
            'enabled' => ['sometimes', 'nullable', 'boolean'],
            'parent' => ['sometimes', 'nullable', 'string', 'max:191'],
            // Owned by this owner or anything beneath it.
            'owner' => ['sometimes', 'nullable', 'string', 'max:191'],
            // In this category or any category beneath it.
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', Rule::enum(ProductStatus::class)],
            'published' => ['sometimes', 'nullable', 'boolean'],
            // Products where anything they show changed at or after this
            // moment — their own data, categories, associations, assets, or
            // what they inherit from a model, family, category, owner or
            // channel. Filters on changed_at.
            'updated_since' => ['sometimes', 'nullable', 'date'],
            // At least `min` percent complete on a channel, in one locale or all of its locales.
            'complete' => ['sometimes', 'nullable', 'array'],
            ...self::completeRules(),
            'filters' => ['sometimes', 'array', 'list', 'max:20'],
            'filters.*' => ['array'],
            ...Arr::prependKeysWith(Filter::rules(), 'filters.*.'),
            'facets' => ['sometimes', 'array', 'list', 'max:10'],
            'facets.*' => ['string', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in($sortable)],
            // Return only one channel's and some locales' values.
        ] + ValueFilter::rules() + self::pagination()->rules();
    }

    /**
     * Complete's rules under `complete.`, required only when it is sent.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function completeRules(): array
    {
        return array_map(
            fn (array $rules): array => array_map(fn (mixed $rule): mixed => $rule === 'required' ? 'required_with:complete' : $rule, $rules),
            Arr::prependKeysWith(Complete::rules(), 'complete.'),
        );
    }

    /**
     * The search, independent of the engine that runs it.
     */
    public function query(): ProductQuery
    {
        $sort = $this->sort ?? 'identifier';
        $descending = str_starts_with($sort, '-');

        return new ProductQuery(
            search: self::filled($this->search),
            family: $this->family,
            enabled: $this->enabled,
            parent: $this->parent,
            owner: self::filled($this->owner),
            category: self::filled($this->category),
            status: $this->status?->value,
            published: $this->published,
            complete: $this->complete,
            filters: $this->filters,
            sort: $descending ? substr($sort, 1) : $sort,
            direction: $descending ? 'desc' : 'asc',
            page: max(1, $this->page ?? 1),
            perPage: $this->perPage ?? self::pagination()->default,
            facets: $this->facets,
            updatedSince: self::filled($this->updatedSince) === null ? null : CarbonImmutable::parse($this->updatedSince),
        );
    }

    private static function filled(?string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    public function valueFilter(): ValueFilter
    {
        return new ValueFilter($this->scope, $this->locales);
    }
}
