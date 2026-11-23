<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Catalog\Enums\SearchType;

/**
 * Input for SearchCatalogAction: the term, an optional single type and how
 * many hits each type may give.
 */
final readonly class SearchCatalogData extends Data
{
    /**
     * How many hits each type gives unless asked otherwise.
     */
    public const int DEFAULT_LIMIT = 5;

    /**
     * The most hits each type may give.
     */
    public const int MAX_LIMIT = 20;

    public function __construct(
        public string $q,
        public ?SearchType $type = null,
        public int $limit = self::DEFAULT_LIMIT,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:191'],
            'type' => ['sometimes', 'nullable', Rule::enum(SearchType::class)],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:'.self::MAX_LIMIT],
        ];
    }

    /**
     * The types to search, in result order.
     *
     * @return list<SearchType>
     */
    public function types(): array
    {
        return SearchType::requested($this->type);
    }
}
