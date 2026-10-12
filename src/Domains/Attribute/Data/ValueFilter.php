<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Attribute\Services\Values;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * Narrows the values a read returns to one channel and some locales, as an
 * export to a single storefront needs. Channel- and locale-independent
 * values always stay.
 */
final readonly class ValueFilter extends Data
{
    /**
     * @param  list<string>|null  $locales
     */
    public function __construct(
        public ?string $scope = null,
        public ?array $locales = null,
    ) {}

    /**
     * The rules for `scope` and `locales`, which the reads that narrow values
     * take among their own.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'scope' => ['sometimes', 'nullable', 'string', Rule::exists(ChannelModel::class, 'code')],
            'locales' => ['sometimes', 'nullable', 'array', 'list'],
            'locales.*' => ['string', Rule::exists(LocaleModel::class, 'code')],
        ];
    }

    public function isEmpty(): bool
    {
        return $this->scope === null && $this->locales === null;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $values
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function apply(array $values): array
    {
        if ($this->isEmpty()) {
            return $values;
        }

        foreach ($values as $code => $channels) {
            foreach ($channels as $channel => $locales) {
                if ($this->scope !== null && $channel !== Values::ALL_CHANNELS && $channel !== $this->scope) {
                    unset($values[$code][$channel]);

                    continue;
                }

                foreach (array_keys($locales) as $locale) {
                    if ($this->locales !== null && $locale !== Values::ALL_LOCALES && ! in_array($locale, $this->locales, true)) {
                        unset($values[$code][$channel][$locale]);
                    }
                }

                if (($values[$code][$channel] ?? null) === []) {
                    unset($values[$code][$channel]);
                }
            }

            if (($values[$code] ?? null) === []) {
                unset($values[$code]);
            }
        }

        return $values;
    }
}
