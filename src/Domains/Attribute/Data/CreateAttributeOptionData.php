<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateAttributeOptionAction.
 */
final readonly class CreateAttributeOptionData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public ?array $labels = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * The code is unique within its attribute, which a static rule cannot
     * express; the Action checks it.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9_]*$/'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
