<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * Input for CreateChannelAction: the channel's code, its locales and,
 * optionally, its labels, currencies and category tree.
 */
final readonly class CreateChannelData extends Data
{
    /**
     * @param  array<int, string>  $locales
     * @param  array<string, string|null>|null  $labels
     * @param  array<int, string>|null  $currencies
     */
    public function __construct(
        public string $code,
        public array $locales,
        public ?array $labels = null,
        public ?array $currencies = null,
        public ?string $categoryTree = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique(ChannelModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'locales' => ['required', 'array', 'list', 'min:1'],
            'locales.*' => ['string', 'distinct', Rule::exists(LocaleModel::class, 'code')],
            'currencies' => ['sometimes', 'nullable', 'array', 'list'],
            'currencies.*' => ['string', 'distinct', 'regex:/^[A-Z]{3}$/'],
            'category_tree' => ['sometimes', 'nullable', 'string', Rule::exists(CategoryModel::class, 'code')],
        ];
    }
}
