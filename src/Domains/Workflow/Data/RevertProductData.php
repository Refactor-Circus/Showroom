<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for RevertProductAction: the version to restore, and why.
 */
final readonly class RevertProductData extends Data
{
    public function __construct(
        public int $version,
        public ?string $comment = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
