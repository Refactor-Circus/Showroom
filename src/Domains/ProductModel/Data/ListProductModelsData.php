<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ProductModel\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListProductModelsAction: optional filters and the page.
 */
final readonly class ListProductModelsData extends Data
{
    public function __construct(
        public ?string $familyVariant = null,
        public ?string $parent = null,
        public bool $roots = false,
        public ?string $search = null,
        public ?string $cursor = null,
        public ?int $perPage = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Paging::cursor();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'family_variant' => ['sometimes', 'nullable', 'string', 'max:100'],
            'parent' => ['sometimes', 'nullable', 'string', 'max:191'],
            'roots' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'nullable', 'string', 'max:191'],
        ] + self::pagination()->rules();
    }
}
