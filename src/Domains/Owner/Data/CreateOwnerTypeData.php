<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use Closure;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
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
            'parent_types.*' => ['string', 'distinct', self::existingOrOwnCode()],
            'can_be_root' => ['sometimes', 'boolean'],
            'owns_products' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * A parent type must exist, or be the type being created: a type may
     * parent itself (a vendor under a vendor), and the action resolves the
     * codes once its row exists.
     *
     * A closure rule rather than an anonymous DataAwareRule class: Laravel
     * hands a closure rule the validator, whose data holds the `code`.
     */
    private static function existingOrOwnCode(): Closure
    {
        /**
         * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
         */
        return static function (string $attribute, mixed $value, Closure $fail, Validator $validator): void {
            if ($value === ($validator->getData()['code'] ?? null)) {
                return;
            }

            if (! OwnerTypeModel::query()->where('code', $value)->exists()) {
                $fail('validation.exists')->translate();
            }
        };
    }
}
