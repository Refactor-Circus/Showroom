<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/**
 * Input for CreateOwnerAction: the owner's code and type, where it sits,
 * and its labels.
 */
final readonly class CreateOwnerData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public string $type,
        public ?string $parent = null,
        public ?array $labels = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', Rule::unique(OwnerModel::class, 'code')],
            'type' => ['required', 'string', Rule::exists(OwnerTypeModel::class, 'code')],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
