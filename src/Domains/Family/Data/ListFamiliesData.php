<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListFamiliesAction: an optional search, the attribute families
 * must have, and the page.
 */
final readonly class ListFamiliesData extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $attribute = null,
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
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'attribute' => ['sometimes', 'nullable', 'string', 'max:100'],
        ] + self::pagination()->rules();
    }
}
