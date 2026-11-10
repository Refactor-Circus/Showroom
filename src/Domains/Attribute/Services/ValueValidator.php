<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * Validates values in standard shape against their attributes and returns
 * them as a storage-shaped patch for Values::apply().
 *
 * Every error is reported at the path the caller sent, such as
 * `values.weight.0.data.unit`.
 *
 * What it reads — attributes, locales, channels — it keeps, so one instance
 * reused across a batch of records reads each once. Resolve a fresh one per
 * write; the container does not share it.
 */
final class ValueValidator
{
    /**
     * Existing locale codes, loaded when first needed.
     *
     * @var array<int, string>|null
     */
    private ?array $locales = null;

    /**
     * Existing channels: code => [locales, currencies], loaded when first needed.
     *
     * @var array<string, array{locales: array<int, string>, currencies: array<int, string>}>|null
     */
    private ?array $channels = null;

    /**
     * The attributes asked for so far, with their options, by code; null for
     * a code no attribute has.
     *
     * @var array<string, AttributeModel|null>
     */
    private array $attributes = [];

    /**
     * @param  Collection<int, AttributeModel>|null  $settable  The attributes this record may set, or null for any.
     * @param  string  $why  Why an attribute outside $settable is refused, completing "AttributeModel "x" ...".
     * @return array<string, array<string, array<string, mixed>>>
     *
     * @throws ValidationException
     */
    public function validate(mixed $values, ?Collection $settable, string $why = 'cannot be set here.'): array
    {
        if ($values === null) {
            return [];
        }

        if (! is_array($values) || ($values !== [] && array_is_list($values))) {
            throw ValidationException::withMessages(['values' => 'Values must be an object keyed by attribute code.']);
        }

        /** @var array<string, mixed> $values */
        $codes = array_map('strval', array_keys($values));

        $attributes = $this->attributes($codes);

        $errors = [];

        foreach ($codes as $code) {
            if (! $attributes->has($code)) {
                $errors['values.'.$code] = sprintf('Attribute "%s" does not exist.', $code);
            } elseif ($settable !== null && ! $settable->contains('code', $code)) {
                $errors['values.'.$code] = sprintf('Attribute "%s" %s', $code, $why);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $this->loadScopes($attributes);

        $rules = ['values' => ['array']];

        foreach ($attributes as $code => $attribute) {
            $rules += $this->rulesFor($attribute, 'values.'.$code);
        }

        /** @var array{values: array<string, array<int, array{locale?: string|null, scope?: string|null, data: mixed}>>} $validated */
        $validated = Validator::validate(['values' => $values], $rules);

        $this->checkChannelSlots($attributes->all(), $validated['values']);

        $patch = [];

        foreach ($validated['values'] as $code => $slots) {
            $attribute = $attributes->get($code);

            if (! $attribute instanceof AttributeModel) {
                continue;
            }

            foreach ($slots as $slot) {
                $scope = $slot['scope'] ?? null;
                $locale = $slot['locale'] ?? null;

                $patch[$code][$scope ?? Values::ALL_CHANNELS][$locale ?? Values::ALL_LOCALES] = $this->normalize($attribute->type, $slot['data']);
            }
        }

        return $patch;
    }

    /**
     * The attributes with these codes, with their options, by code: read
     * once per code for the life of this validator.
     *
     * @param  array<int, string>  $codes
     * @return Collection<string, AttributeModel>
     */
    private function attributes(array $codes): Collection
    {
        $unread = array_values(array_diff($codes, array_keys($this->attributes)));

        if ($unread !== []) {
            foreach (AttributeModel::query()->with('options')->whereIn('code', $unread)->get() as $attribute) {
                $this->attributes[$attribute->code] = $attribute;
            }

            foreach ($unread as $code) {
                $this->attributes[$code] ??= null;
            }
        }

        /** @var Collection<string, AttributeModel> $attributes */
        $attributes = collect($codes)
            ->mapWithKeys(fn (string $code): array => [$code => $this->attributes[$code] ?? null])
            ->filter();

        return $attributes;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(AttributeModel $attribute, string $path): array
    {
        $settings = $attribute->settings ?? [];

        $rules = [
            $path => ['array', 'list', 'min:1'],
            $path.'.*' => ['array'],
            $path.'.*.locale' => $attribute->is_localizable
                ? ['required', 'string', Rule::in($this->locales ?? [])]
                : ['nullable', 'prohibited'],
            $path.'.*.scope' => $attribute->is_scopable
                ? ['required', 'string', Rule::in(array_keys($this->channels ?? []))]
                : ['nullable', 'prohibited'],
        ];

        $data = $path.'.*.data';
        $options = $attribute->options->map(fn (AttributeOptionModel $option): string => $option->code)->all();

        return $rules + match ($attribute->type) {
            AttributeType::Text => [$data => array_filter([
                'present', 'nullable', 'string', 'max:'.($settings['max_length'] ?? 255),
                is_string($settings['regex'] ?? null) ? 'regex:'.$settings['regex'] : null,
            ])],
            AttributeType::Textarea => [$data => ['present', 'nullable', 'string', 'max:'.($settings['max_length'] ?? 65535)]],
            AttributeType::Number => [$data => $this->bounded(['present', 'nullable', 'integer'], $settings)],
            AttributeType::Decimal => [$data => $this->bounded(
                ['present', 'nullable', 'numeric', 'decimal:0,'.($settings['decimals'] ?? 4)],
                $settings,
            )],
            AttributeType::Boolean => [$data => ['present', 'nullable', 'boolean']],
            AttributeType::Date => [$data => array_filter([
                'present', 'nullable', 'date_format:Y-m-d',
                is_string($settings['min'] ?? null) ? 'after_or_equal:'.$settings['min'] : null,
                is_string($settings['max'] ?? null) ? 'before_or_equal:'.$settings['max'] : null,
            ])],
            AttributeType::Select => [$data => ['present', 'nullable', 'string', Rule::in($options)]],
            AttributeType::Multiselect => [
                $data => ['present', 'nullable', 'array', 'list'],
                $data.'.*' => ['string', 'distinct', Rule::in($options)],
            ],
            AttributeType::Price => [
                $data => ['present', 'nullable', 'array', 'list'],
                $data.'.*' => ['array'],
                $data.'.*.amount' => ['required', 'numeric', 'decimal:0,'.($settings['decimals'] ?? 2)],
                $data.'.*.currency' => array_filter([
                    'required', 'string', 'distinct', 'regex:/^[A-Z]{3}$/',
                    is_array($settings['currencies'] ?? null) && $settings['currencies'] !== [] ? Rule::in($settings['currencies']) : null,
                ]),
            ],
            AttributeType::Metric => [
                $data => ['present', 'nullable', 'array'],
                $data.'.amount' => ['required_with:'.$data, 'numeric'],
                $data.'.unit' => ['required_with:'.$data, 'string', 'max:100'],
            ],
        };
    }

    /**
     * Load the locales and channels, but only when some attribute needs them,
     * and only once.
     *
     * @param  iterable<string, AttributeModel>  $attributes
     */
    private function loadScopes(iterable $attributes): void
    {
        $localizable = false;
        $scopable = false;

        foreach ($attributes as $attribute) {
            $localizable = $localizable || $attribute->is_localizable;
            $scopable = $scopable || $attribute->is_scopable;
        }

        if ($localizable && $this->locales === null) {
            /** @var array<int, string> $codes */
            $codes = LocaleModel::query()->pluck('code')->all();
            $this->locales = $codes;
        }

        if ($scopable && $this->channels === null) {
            $this->channels = [];

            foreach (ChannelModel::query()->with('locales')->get() as $channel) {
                $this->channels[$channel->code] = [
                    'locales' => $channel->locales->pluck('code')->all(),
                    'currencies' => $channel->currencies ?? [],
                ];
            }
        }
    }

    /**
     * A channel publishes in its own locales and sells in its own currencies,
     * so a value scoped to it is held to both.
     *
     * @param  array<string, AttributeModel>  $attributes
     * @param  array<string, array<int, array{locale?: string|null, scope?: string|null, data: mixed}>>  $values
     *
     * @throws ValidationException
     */
    private function checkChannelSlots(array $attributes, array $values): void
    {
        $errors = [];

        foreach ($values as $code => $slots) {
            $attribute = $attributes[$code] ?? null;

            if (! $attribute instanceof AttributeModel || ! $attribute->is_scopable) {
                continue;
            }

            foreach ($slots as $index => $slot) {
                $scope = $slot['scope'] ?? '';
                $channel = $this->channels[$scope] ?? null;

                if ($channel === null) {
                    continue;
                }

                $locale = $slot['locale'] ?? null;

                if ($attribute->is_localizable && $locale !== null && ! in_array($locale, $channel['locales'], true)) {
                    $errors[sprintf('values.%s.%d.locale', $code, $index)] = sprintf(
                        'Channel "%s" does not publish in locale "%s". Its locales: %s.',
                        $scope,
                        $locale,
                        implode(', ', $channel['locales']),
                    );
                }

                if ($attribute->type === AttributeType::Price && is_array($slot['data']) && $channel['currencies'] !== []) {
                    foreach ($slot['data'] as $position => $price) {
                        $currency = is_array($price) ? ($price['currency'] ?? null) : null;

                        if (is_string($currency) && ! in_array($currency, $channel['currencies'], true)) {
                            $errors[sprintf('values.%s.%d.data.%d.currency', $code, $index, $position)] = sprintf(
                                'Channel "%s" does not sell in %s. Its currencies: %s.',
                                $scope,
                                $currency,
                                implode(', ', $channel['currencies']),
                            );
                        }
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<int, string>  $rules
     * @param  array<string, mixed>  $settings
     * @return array<int, string>
     */
    private function bounded(array $rules, array $settings): array
    {
        if (is_numeric($settings['min'] ?? null)) {
            $rules[] = 'min:'.$settings['min'];
        }

        if (is_numeric($settings['max'] ?? null)) {
            $rules[] = 'max:'.$settings['max'];
        }

        return $rules;
    }

    /**
     * One canonical form per type: integers as ints, decimals and amounts as
     * strings so no precision is lost to floats, option lists in order sent.
     */
    private function normalize(AttributeType $type, mixed $data): mixed
    {
        if ($data === null) {
            return null;
        }

        return match ($type) {
            AttributeType::Number => is_numeric($data) ? (int) $data : $data,
            AttributeType::Decimal => is_numeric($data) ? (string) $data : $data,
            AttributeType::Boolean => filter_var($data, FILTER_VALIDATE_BOOLEAN),
            AttributeType::Price => is_array($data) ? array_values(array_map(
                fn (mixed $price): array => [
                    'amount' => is_array($price) && is_numeric($price['amount'] ?? null) ? (string) $price['amount'] : '0',
                    'currency' => is_array($price) && is_string($price['currency'] ?? null) ? $price['currency'] : '',
                ],
                $data,
            )) : $data,
            AttributeType::Metric => is_array($data) ? [
                'amount' => is_numeric($data['amount'] ?? null) ? (string) $data['amount'] : '0',
                'unit' => is_string($data['unit'] ?? null) ? $data['unit'] : '',
            ] : $data,
            default => $data,
        };
    }
}
