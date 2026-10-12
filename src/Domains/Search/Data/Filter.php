<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;

/**
 * One condition on an attribute value: `{"attribute": "color", "operator":
 * "in", "value": ["red", "blue"]}`, with `locale` and `scope` for localizable
 * and scopable attributes.
 */
final readonly class Filter extends Data
{
    public const array OPERATORS = ['=', '!=', 'in', 'not_in', '>', '>=', '<', '<=', 'empty', 'not_empty'];

    public function __construct(
        public string $attribute,
        public string $operator,
        public mixed $value = null,
        public ?string $locale = null,
        public ?string $scope = null,
    ) {}

    /**
     * A condition's rules; a search takes its list under `filters.*`.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:100'],
            'operator' => ['required', 'string', Rule::in(self::OPERATORS)],
            'value' => ['nullable'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:20'],
            'scope' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * The value as a list, for `in` and `not_in`.
     *
     * @return array<int, mixed>
     */
    public function values(): array
    {
        return is_array($this->value) ? array_values($this->value) : [$this->value];
    }
}
