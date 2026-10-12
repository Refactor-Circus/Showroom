<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Data;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

/**
 * Input for CreateFamilyVariantAction. `levels` lists one or two levels,
 * each with its axes and the other attributes set there:
 * `[{"axes": ["color"], "attributes": ["image"]}]`.
 */
final readonly class CreateFamilyVariantData extends Data
{
    /**
     * @param  list<FamilyLevelData>  $levels
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public string $family,
        #[ListOf(FamilyLevelData::class)]
        public array $levels,
        public ?array $labels = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(FamilyVariantModel::class, 'code')],
            'family' => ['required', 'string', Rule::exists(FamilyModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ] + self::levelRules(required: true);
    }

    /**
     * The level rules creating and updating a family variant share.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function levelRules(bool $required): array
    {
        return [
            'levels' => [$required ? 'required' : 'sometimes', 'array', 'list', 'min:1', 'max:2'],
            'levels.*' => ['array'],
            ...Arr::prependKeysWith(FamilyLevelData::rules(), 'levels.*.'),
        ];
    }
}
