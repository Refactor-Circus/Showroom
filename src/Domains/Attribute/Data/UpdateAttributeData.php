<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

/**
 * Input for UpdateAttributeAction: the attribute fields to change. Fields
 * left out are kept.
 */
final readonly class UpdateAttributeData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  array<string, mixed>|null|Missing  $settings  Replaced whole.
     */
    public function __construct(
        public string|null|Missing $group = new Missing,
        public array|null|Missing $labels = new Missing,
        public bool|Missing $isUnique = new Missing,
        public bool|Missing $isLocalizable = new Missing,
        public bool|Missing $isScopable = new Missing,
        public array|null|Missing $settings = new Missing,
        public int|Missing $sortOrder = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // Every stored value depends on the code and type, so neither
            // changes once the attribute exists.
            'code' => ['prohibited'],
            'type' => ['prohibited'],
            'group' => ['sometimes', 'nullable', 'string', Rule::exists(AttributeGroupModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'is_unique' => ['sometimes', 'boolean'],
            'is_localizable' => ['sometimes', 'boolean'],
            'is_scopable' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'nullable', 'array'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
