<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Data;

use Carbon\CarbonImmutable;

/**
 * A product search, independent of the engine that runs it. Engines take it
 * from `ListProductsData::query()`, which builds it from validated input.
 */
final readonly class ProductQuery
{
    public const array SORTABLE = ['identifier', 'created_at', 'updated_at'];

    /**
     * @param  list<Filter>  $filters
     * @param  'asc'|'desc'  $direction
     * @param  list<string>  $facets  Attribute codes to count values of.
     */
    public function __construct(
        public ?string $search = null,
        public ?string $family = null,
        public ?bool $enabled = null,
        public ?string $parent = null,
        public ?string $owner = null,
        public ?string $category = null,
        public ?string $status = null,
        public ?bool $published = null,
        public ?Complete $complete = null,
        public array $filters = [],
        public string $sort = 'identifier',
        public string $direction = 'asc',
        public int $page = 1,
        public int $perPage = 25,
        public array $facets = [],
        public ?CarbonImmutable $updatedSince = null,
    ) {}

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
