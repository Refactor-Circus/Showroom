<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;

/**
 * Input for UpdateFlagAction: the flag fields to change. Fields left out are
 * kept. A kind of record the flag is still assigned to cannot leave
 * `applies_to`.
 */
final readonly class UpdateFlagData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  list<string>|Missing  $appliesTo  "product", "owner" or both.
     */
    public function __construct(
        public array|null|Missing $labels = new Missing,
        public array|Missing $appliesTo = new Missing,
        public int|Missing $sortOrder = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // Assignments, search filters and API clients name a flag by code; it never changes.
            'code' => ['prohibited'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'applies_to' => ['sometimes', 'required', 'array', 'list', 'min:1'],
            'applies_to.*' => ['string', 'distinct', Rule::in(FlagSubject::values())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
