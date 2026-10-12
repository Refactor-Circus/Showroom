<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * Input for CreateAttributeAction.
 */
final readonly class CreateAttributeData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     * @param  array<string, mixed>|null  $settings  Type-specific settings.
     */
    public function __construct(
        public string $code,
        public AttributeType $type,
        public ?string $group = null,
        public ?array $labels = null,
        public bool $isUnique = false,
        public bool $isLocalizable = false,
        public bool $isScopable = false,
        public ?array $settings = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * Type-specific settings are checked against the type by the Action,
     * since the rules for them depend on the value of `type`.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(AttributeModel::class, 'code')],
            'type' => ['required', Rule::enum(AttributeType::class)],
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
