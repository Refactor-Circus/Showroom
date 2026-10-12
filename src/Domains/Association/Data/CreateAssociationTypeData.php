<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;

/**
 * Input for CreateAssociationTypeAction.
 */
final readonly class CreateAssociationTypeData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public ?array $labels = null,
        public bool $isTwoWay = false,
        public bool $isQuantified = false,
    ) {}

    /**
     * `is_two_way` and `is_quantified` are fixed once created, and a type is
     * not both: a quantity only makes sense in one direction.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(AssociationTypeModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'is_two_way' => ['sometimes', 'boolean'],
            'is_quantified' => ['sometimes', 'boolean', 'declined_if:is_two_way,true,1'],
        ];
    }
}
