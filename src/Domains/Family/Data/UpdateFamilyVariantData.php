<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Data;

use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateFamilyVariantAction: the labels and levels to change.
 * `levels` can change only while no product model uses the variant. Fields
 * left out are kept.
 */
final readonly class UpdateFamilyVariantData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  list<FamilyLevelData>|Missing  $levels
     */
    public function __construct(
        public array|null|Missing $labels = new Missing,
        #[ListOf(FamilyLevelData::class)]
        public array|Missing $levels = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'family' => ['prohibited'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ] + CreateFamilyVariantData::levelRules(required: false);
    }
}
