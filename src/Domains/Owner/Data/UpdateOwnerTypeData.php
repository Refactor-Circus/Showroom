<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/**
 * Input for UpdateOwnerTypeAction: the type fields to change. Fields left
 * out are kept. The rules apply to later writes; owners already placed stay
 * where they are.
 */
final readonly class UpdateOwnerTypeData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  array<int, string>|null|Missing  $parentTypes  null: any type may be the parent; a list: only these, [] for none.
     */
    public function __construct(
        public array|null|Missing $labels = new Missing,
        public array|null|Missing $parentTypes = new Missing,
        public bool|Missing $canBeRoot = new Missing,
        public bool|Missing $ownsProducts = new Missing,
        public int|Missing $sortOrder = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['prohibited'],
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
