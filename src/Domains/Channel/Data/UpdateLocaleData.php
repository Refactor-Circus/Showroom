<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateLocaleAction: the labels, the one thing that changes.
 * Left out, they are kept.
 */
final readonly class UpdateLocaleData extends Data
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
            // Stored values are keyed by locale code; it never changes.
            'code' => ['prohibited'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
