<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * Input for CreateFlagAction: the flag's code, the kinds of record it may be
 * assigned to and, optionally, its labels and sort order.
 */
final readonly class CreateFlagData extends Data
{
    /**
     * @param  list<string>  $appliesTo  "product", "owner" or both.
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public array $appliesTo,
        public ?array $labels = null,
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(FlagModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'applies_to' => ['required', 'array', 'list', 'min:1'],
            'applies_to.*' => ['string', 'distinct', Rule::in(FlagSubject::values())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
