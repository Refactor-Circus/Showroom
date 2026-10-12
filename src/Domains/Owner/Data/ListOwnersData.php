<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListOwnersAction: where in the tree to look, an optional search and the page.
 */
final readonly class ListOwnersData extends Data
{
    public function __construct(
        public ?string $type = null,
        public ?string $parent = null,
        public ?string $under = null,
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
            'type' => ['sometimes', 'nullable', 'string', 'max:100'],
            // Direct children of this owner code.
            'parent' => ['sometimes', 'nullable', 'string', 'max:191'],
            // Everything beneath this owner code, at any depth.
            'under' => ['sometimes', 'nullable', 'string', 'max:191'],
            'roots' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'nullable', 'string', 'max:191'],
        ] + self::pagination()->rules();
    }
}
