<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListAssetsAction: an optional search, MIME type and linked
 * record, and the page.
 */
final readonly class ListAssetsData extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?string $type = null,
        public ?string $product = null,
        public ?string $productModel = null,
        public ?string $owner = null,
        public ?string $role = null,
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
            'search' => ['sometimes', 'nullable', 'string', 'max:191'],
            // A MIME type, or a family of them such as "image/*".
            'type' => ['sometimes', 'nullable', 'string', 'max:191'],
            'product' => ['sometimes', 'nullable', 'string', 'max:191'],
            'product_model' => ['sometimes', 'nullable', 'string', 'max:191'],
            'owner' => ['sometimes', 'nullable', 'string', 'max:191'],
            'role' => ['sometimes', 'nullable', 'string', 'max:100'],
        ] + self::pagination()->rules();
    }
}
