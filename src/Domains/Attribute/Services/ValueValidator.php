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
 * What it reads — attributes, locales, channels, option codes — it keeps,
 * so one instance reused across a batch of records reads each once. Resolve
 * a fresh one per write; the container does not share it.
 *
 * Options are never loaded whole: an attribute may hold thousands. Only the
 * codes a select or multiselect value sends are looked up, those of one
 * validate() call in one query, or those of a whole batch at once through
 * prime(). A value is checked against the stored codes it sends, so a check
 * costs the same however many options its attribute has.
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
     * The attributes asked for so far, by code; null for a code no attribute
     * has.
     *
     * @var array<string, AttributeModel|null>
     */
    private array $attributes = [];

    /**
     * What the plain-value checks read of each attribute read so far, by
     * code: read off the model once, as each cast attribute is decoded
     * again on every read.
     *
     * @var array<string, array{id: string, type: AttributeType, localizable: bool, scopable: bool, settings: array<string, mixed>}>
     */
    private array $profiles = [];

    /**
     * The option codes looked up so far that are stored, as stored, by
     * attribute id.
     *
     * @var array<string, array<string, true>>
     */
    private array $optionCodes = [];

    /**
     * The option codes looked up so far, as sent, by attribute id: stored or
     * not, they are not read again.
     *
     * @var array<string, array<string, true>>
     */
    private array $checkedCodes = [];

    /**
     * Read up front the attributes and the option codes a batch of value maps
     * sends, so validating each map reads nothing more: one query for the
     * attributes and one for the option codes, a thousand codes per query.
     * Anything that is not a value map is passed over; validate() refuses it.
     *
     * @param  iterable<mixed>  $valueMaps  Values in standard shape, one map per record.
     */
    public function prime(iterable $valueMaps): void
    {
        $maps = [];

        foreach ($valueMaps as $values) {
            if (is_array($values) && ($values === [] || ! array_is_list($values))) {
                $maps[] = $values;
            }
        }

        $codes = [];

        foreach ($maps as $values) {
            foreach (array_keys($values) as $code) {
                $codes[(string) $code] = (string) $code;
            }
        }

        $attributes = $this->attributes(array_values($codes));
        // Gathered into one map, not one per record: a batch sends tens of thousands.
        $sent = [];

        foreach ($maps as $values) {
            foreach ($this->sentOptionCodes($attributes, $values) as $attributeId => $optionCodes) {
                foreach ($optionCodes as $optionCode) {
                    $sent[$attributeId][$optionCode] = $optionCode;
                }
            }
        }

        $this->loadOptionCodes([array_map(array_values(...), $sent)]);
    }

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
        $sentOptions = $this->sentOptionCodes($attributes, $values);
        $this->loadOptionCodes([$sentOptions]);

        $errors = [];
        $settableCodes = $settable === null ? null : array_flip(array_map(strval(...), $settable->pluck('code')->all()));

        foreach ($codes as $code) {
            if (! $attributes->has($code)) {
                $errors['values.'.$code] = sprintf('Attribute "%s" does not exist.', $code);
            } elseif ($settableCodes !== null && ! isset($settableCodes[$code])) {
                $errors['values.'.$code] = sprintf('Attribute "%s" %s', $code, $why);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $patch = $this->quickPatch($attributes, $values);

        if ($patch !== null) {
            return $patch;
        }

        $this->loadScopes($attributes);

        $rules = ['values' => ['array']];

        foreach ($attributes as $code => $attribute) {
            $rules += $this->rulesFor($attribute, 'values.'.$code, $sentOptions[$attribute->id] ?? []);
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
     * The patch of a value map whose every value is plainly valid, checked
     * without the validator; null when any value needs the full rules.
     *
     * Building and running the rules costs far more than the values: a batch
     * of a thousand records with thirty values each spent seconds there. So
     * the common values — no locale, no scope, of a type whose rules are
     * simple — are checked here, and accepted only when the rules surely
     * accept them. Anything else, an invalid value included, goes through the
     * rules, so every error keeps its key and message.
     *
     * @param  Collection<string, AttributeModel>  $attributes  By code, every code of $values.
     * @param  array<array-key, mixed>  $values
     * @return array<string, array<string, array<string, mixed>>>|null
     */
    private function quickPatch(Collection $attributes, array $values): ?array
    {
        $patch = [];

        foreach ($values as $code => $slots) {
            $profile = $attributes->has((string) $code) ? ($this->profiles[(string) $code] ?? null) : null;

            // As `array`, `list` and `min:1` take the slots.
            if ($profile === null || $profile['localizable'] || $profile['scopable']
                || ! is_array($slots) || $slots === [] || ! array_is_list($slots)) {
                return null;
            }

            foreach ($slots as $slot) {
                // A locale or scope, even an empty one, is left to the rules.
                if (! is_array($slot) || array_key_exists('locale', $slot) && $slot['locale'] !== null
                    || array_key_exists('scope', $slot) && $slot['scope'] !== null
                    || ! array_key_exists('data', $slot) || ! $this->plainlyValid($profile, $slot['data'])) {
                    return null;
                }

                $patch[(string) $code][Values::ALL_CHANNELS][Values::ALL_LOCALES] = $this->normalize($profile['type'], $slot['data']);
            }
        }

        return $patch;
    }

    /**
     * Whether rulesFor() surely accepts this data for the attribute. False
     * means only that the full rules must decide.
     *
     * @param  array{id: string, type: AttributeType, localizable: bool, scopable: bool, settings: array<string, mixed>}  $profile
     */
    private function plainlyValid(array $profile, mixed $data): bool
    {
        // Every type's data is `nullable`.
        if ($data === null) {
            return true;
        }

        $settings = $profile['settings'];
        $known = $this->optionCodes[$profile['id']] ?? [];

        return match ($profile['type']) {
            // `in` compares strictly, against the stored codes.
            AttributeType::Select => is_string($data) && isset($known[$data]),
            AttributeType::Multiselect => is_array($data) && array_is_list($data)
                && array_all($data, fn (mixed $code): bool => is_string($code) && isset($known[$code]))
                // `distinct` compares loosely.
                && count(array_unique($data, SORT_REGULAR)) === count($data),
            AttributeType::Text => is_string($data) && ! is_string($settings['regex'] ?? null)
                && mb_strlen($data) <= (int) ($settings['max_length'] ?? 255),
            AttributeType::Textarea => is_string($data) && mb_strlen($data) <= (int) ($settings['max_length'] ?? 65535),
            AttributeType::Boolean => is_bool($data),
            AttributeType::Number => is_int($data)
                && (! is_numeric($settings['min'] ?? null) || $data >= $settings['min'])
                && (! is_numeric($settings['max'] ?? null) || $data <= $settings['max']),
            default => false,
        };
    }

    /**
     * The attributes with these codes, by code: read once per code for the
     * life of this validator.
     *
     * @param  array<int, string>  $codes
     * @return Collection<string, AttributeModel>
     */
    private function attributes(array $codes): Collection
    {
        $unread = array_values(array_diff($codes, array_keys($this->attributes)));

        if ($unread !== []) {
            foreach (AttributeModel::query()->whereIn('code', $unread)->get() as $attribute) {
                $this->attributes[$attribute->code] = $attribute;
                $this->profiles[$attribute->code] = [
                    'id' => $attribute->id,
                    'type' => $attribute->type,
                    'localizable' => $attribute->is_localizable,
                    'scopable' => $attribute->is_scopable,
                    'settings' => $attribute->settings ?? [],
                ];
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
     * The option codes a value map sends for its select and multiselect
     * attributes, by attribute id. Only strings: anything else fails the
     * value's `string` rule.
     *
     * @param  Collection<string, AttributeModel>  $attributes  By code.
     * @param  array<array-key, mixed>  $values
     * @return array<string, list<string>>
     */
    private function sentOptionCodes(Collection $attributes, array $values): array
    {
        $sent = [];

        foreach ($values as $code => $slots) {
            $profile = $attributes->has((string) $code) ? ($this->profiles[(string) $code] ?? null) : null;

            if ($profile === null || ! $profile['type']->hasOptions() || ! is_array($slots)) {
                continue;
            }

            foreach ($slots as $slot) {
                $data = is_array($slot) ? ($slot['data'] ?? null) : null;

                foreach (is_array($data) ? $data : [$data] as $option) {
                    if (is_string($option)) {
                        $sent[$profile['id']][$option] = $option;
                    }
                }
            }
        }

        return array_map(array_values(...), $sent);
    }

    /**
     * Look up which of the sent option codes are stored, skipping those
     * looked up before: one query per thousand codes for all the maps.
     *
     * @param  iterable<array<string, list<string>>>  $sentPerMap  Option codes by attribute id, per value map.
     */
    private function loadOptionCodes(iterable $sentPerMap): void
    {
        $unread = [];

        foreach ($sentPerMap as $sent) {
            foreach ($sent as $attributeId => $codes) {
                foreach ($codes as $code) {
                    if (! isset($this->checkedCodes[$attributeId][$code])) {
                        $unread[$attributeId][$code] = $code;
                        $this->checkedCodes[$attributeId][$code] = true;
                    }
                }
            }
        }

        if ($unread === []) {
            return;
        }

        $attributeIds = array_map('strval', array_keys($unread));
        $codes = array_values(array_unique(array_merge(...array_map(array_values(...), array_values($unread)))));

        foreach (array_chunk($codes, 1000) as $chunk) {
            // Plain rows: an option model is never built here.
            $stored = AttributeOptionModel::query()->toBase()
                ->whereIn('attribute_id', $attributeIds)
                ->whereIn('code', $chunk)
                ->select(['attribute_id', 'code'])
                // Streamed: codes sent for one attribute can match many rows of the others.
                ->cursor();

            foreach ($stored as $option) {
                $this->optionCodes[(string) $option->attribute_id][(string) $option->code] = true;
            }
        }
    }

    /**
     * The sent codes that are stored options of the attribute, compared
     * exactly, as `in` compares them.
     *
     * @param  list<string>  $sent
     * @return list<string>
     */
    private function knownOptionCodes(AttributeModel $attribute, array $sent): array
    {
        $known = $this->optionCodes[$attribute->id] ?? [];

        return array_values(array_filter($sent, fn (string $code): bool => isset($known[$code])));
    }

    /**
     * @param  list<string>  $sentOptions  The option codes this value sends, for a select or multiselect attribute.
     * @return array<string, array<int, mixed>>
     */
    private function rulesFor(AttributeModel $attribute, string $path, array $sentOptions = []): array
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
        // Only the sent codes found stored: `in` over them is the same check
        // as over every option, at the cost of the value, not the attribute.
        $options = $attribute->type->hasOptions() ? $this->knownOptionCodes($attribute, $sentOptions) : [];

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
