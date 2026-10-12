<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Support\Paging;

/**
 * Input for ListAttributesAction: optional type, group and search filters, and the page.
 */
final readonly class ListAttributesData extends Data
{
    public function __construct(
        public ?AttributeType $type = null,
        public ?string $group = null,
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
            'type' => ['sometimes', 'nullable', Rule::enum(AttributeType::class)],
            'group' => ['sometimes', 'nullable', 'string', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ] + self::pagination()->rules();
    }
}
