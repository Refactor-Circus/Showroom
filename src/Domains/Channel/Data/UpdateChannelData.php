<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * Input for UpdateChannelAction: the channel fields to change. Fields left
 * out are kept; values already written in a locale the channel drops stay
 * stored.
 */
final readonly class UpdateChannelData extends Data
{
    /**
     * @param  array<int, string>|Missing  $locales
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  array<int, string>|null|Missing  $currencies
     */
    public function __construct(
        public array|Missing $locales = new Missing,
        public array|null|Missing $labels = new Missing,
        public array|null|Missing $currencies = new Missing,
        public string|null|Missing $categoryTree = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // Scopable values are keyed by channel code; it never changes.
            'code' => ['prohibited'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'locales' => ['sometimes', 'array', 'list', 'min:1'],
            'locales.*' => ['string', 'distinct', Rule::exists(LocaleModel::class, 'code')],
            'currencies' => ['sometimes', 'nullable', 'array', 'list'],
            'currencies.*' => ['string', 'distinct', 'regex:/^[A-Z]{3}$/'],
            'category_tree' => ['sometimes', 'nullable', 'string', Rule::exists(CategoryModel::class, 'code')],
        ];
    }
}
