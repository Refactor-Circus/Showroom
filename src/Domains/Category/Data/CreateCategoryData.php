<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Category\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;

/**
 * Input for CreateCategoryAction. A category without a parent starts a new
 * tree.
 */
final readonly class CreateCategoryData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public ?string $parent = null,
        public ?array $labels = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9_]*$/', Rule::unique(CategoryModel::class, 'code')],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(CategoryModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
