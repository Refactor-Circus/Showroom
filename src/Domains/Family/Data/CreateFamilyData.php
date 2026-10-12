<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Data;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

/**
 * Input for CreateFamilyAction. `attributes` is the family's whole
 * membership: `[{"attribute": "color", "is_required": true}]`.
 */
final readonly class CreateFamilyData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     * @param  list<FamilyAttributeData>|null  $attributes
     */
    public function __construct(
        public string $code,
        public ?array $labels = null,
        public ?string $labelAttribute = null,
        #[ListOf(FamilyAttributeData::class)]
        public ?array $attributes = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(FamilyModel::class, 'code')],
        ] + self::familyRules();
    }

    /**
     * The membership and label rules creating and updating a family share.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function familyRules(): array
    {
        return [
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'label_attribute' => ['sometimes', 'nullable', 'string', Rule::exists(AttributeModel::class, 'code')],
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['array'],
            ...Arr::prependKeysWith(FamilyAttributeData::rules(), 'attributes.*.'),
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
