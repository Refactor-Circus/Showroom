<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * One level of a family variant: the axes its products vary on and the
 * other attributes set at that level.
 */
final readonly class FamilyLevelData extends Data
{
    /**
     * @param  list<string>  $axes
     * @param  list<string>  $attributes
     */
    public function __construct(
        public array $axes,
        public array $attributes = [],
    ) {}

    /**
     * A level's rules; a family variant's levels take them under `levels.*`.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'axes' => ['required', 'array', 'list', 'min:1', 'max:5'],
            'axes.*' => ['string', Rule::exists(AttributeModel::class, 'code')],
            'attributes' => ['sometimes', 'array', 'list'],
            'attributes.*' => ['string', Rule::exists(AttributeModel::class, 'code')],
        ];
    }
}
