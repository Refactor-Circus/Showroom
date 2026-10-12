<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListProductVersionsAction: optionally one action's versions, and
 * the page.
 */
final readonly class ListProductVersionsData extends Data
{
    public function __construct(
        public ?string $action = null,
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
            'action' => ['sometimes', 'nullable', 'string', 'max:32'],
        ] + self::pagination()->rules();
    }
}
