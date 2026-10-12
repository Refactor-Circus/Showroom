<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

/**
 * Input for CreateAttributeGroupAction.
 */
final readonly class CreateAttributeGroupData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public ?array $labels = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(AttributeGroupModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
