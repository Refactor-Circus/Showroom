<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/**
 * Input for CreateOwnerTypeAction: the type's code and how owners of it may
 * be placed.
 */
final readonly class CreateOwnerTypeData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     * @param  array<int, string>|null  $parentTypes  null: any type may be the parent; a list: only these, [] for none.
     */
    public function __construct(
        public string $code,
        public ?array $labels = null,
        public ?array $parentTypes = null,
        public bool $canBeRoot = true,
        public bool $ownsProducts = true,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(OwnerTypeModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            // null: any type may be the parent; a list: only these, [] for none.
            'parent_types' => ['sometimes', 'nullable', 'array', 'list'],
            'parent_types.*' => ['string', 'distinct', Rule::exists(OwnerTypeModel::class, 'code')],
            'can_be_root' => ['sometimes', 'boolean'],
            'owns_products' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
