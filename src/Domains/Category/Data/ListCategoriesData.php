<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListCategoriesAction: which part of the trees to list, an
 * optional search, and the page.
 */
final readonly class ListCategoriesData extends Data
{
    public function __construct(
        public bool $roots = false,
        public ?string $parent = null,
        public ?string $under = null,
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
            // Only tree roots: the list of category trees.
            'roots' => ['sometimes', 'boolean'],
            // Direct children of this category code.
            'parent' => ['sometimes', 'nullable', 'string', 'max:100'],
            // Everything beneath this category code, at any depth.
            'under' => ['sometimes', 'nullable', 'string', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ] + self::pagination()->rules();
    }
}
