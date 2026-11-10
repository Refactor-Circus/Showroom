<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * What UpsertProductsAction did with a batch: how many records it created,
 * updated, found unchanged and skipped (the mode left them out), and each
 * record it refused, by its index in the batch, with its identifier and the
 * errors by field.
 */
final readonly class UpsertProductsResult extends Data
{
    /**
     * @param  list<array{index: int, identifier: string|null, errors: array<string, list<string>>}>  $failures  In batch order.
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $unchanged = 0,
        public int $skipped = 0,
        public array $failures = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'created' => ['sometimes', 'integer', 'min:0'],
            'updated' => ['sometimes', 'integer', 'min:0'],
            'unchanged' => ['sometimes', 'integer', 'min:0'],
            'skipped' => ['sometimes', 'integer', 'min:0'],
            'failures' => ['sometimes', 'array', 'list'],
            'failures.*.index' => ['required', 'integer', 'min:0'],
            'failures.*.identifier' => ['present', 'nullable', 'string'],
            'failures.*.errors' => ['required', 'array'],
        ];
    }

    /**
     * How many records were refused.
     */
    public function failed(): int
    {
        return count($this->failures);
    }
}
