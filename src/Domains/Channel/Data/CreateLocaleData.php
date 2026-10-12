<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * Input for CreateLocaleAction: the locale's code and, optionally, labels.
 */
final readonly class CreateLocaleData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public string $code,
        public ?array $labels = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // A language, optionally with a region or script: en, en_US, zh-Hant.
            'code' => ['required', 'string', 'max:20', 'regex:/^[a-z]{2,3}([_-][A-Za-z0-9]{2,8})*$/', Rule::unique(LocaleModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
