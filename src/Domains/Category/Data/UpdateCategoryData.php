<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;

/**
 * Input for UpdateCategoryAction: the fields to change. Sending `parent`
 * moves the category with its whole branch - within its tree, into another,
 * or (null) to the root as a tree of its own. Fields left out are kept.
 */
final readonly class UpdateCategoryData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     */
    public function __construct(
        public string|null|Missing $parent = new Missing,
        public array|null|Missing $labels = new Missing,
        public int|Missing $sortOrder = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(CategoryModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
