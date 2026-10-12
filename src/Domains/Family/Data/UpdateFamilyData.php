<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Data;

use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateFamilyAction: the fields to change. `attributes`, when
 * given, replaces the family's whole membership. Fields left out are kept.
 */
final readonly class UpdateFamilyData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  list<FamilyAttributeData>|Missing  $attributes
     */
    public function __construct(
        public array|null|Missing $labels = new Missing,
        public string|null|Missing $labelAttribute = new Missing,
        #[ListOf(FamilyAttributeData::class)]
        public array|Missing $attributes = new Missing,
        public int|Missing $sortOrder = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // A code is an identifier other systems hold on to; it never changes.
            'code' => ['prohibited'],
        ] + CreateFamilyData::familyRules();
    }
}
