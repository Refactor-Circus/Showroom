<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateAssociationTypeAction: the labels, the one thing that
 * changes. Left out, they are kept.
 */
final readonly class UpdateAssociationTypeData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     */
    public function __construct(
        public array|null|Missing $labels = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // Existing associations are shaped by these; they never change.
            'code' => ['prohibited'],
            'is_two_way' => ['prohibited'],
            'is_quantified' => ['prohibited'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
