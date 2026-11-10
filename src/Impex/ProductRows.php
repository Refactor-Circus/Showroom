<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Impex;

use Illuminate\Support\Collection;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * Converts between product records in API shape and flat CSV rows, with
 * Akeneo's column conventions:
 *
 * - `identifier`, `family`, `parent`, `enabled` (1/0), `categories` (comma-separated);
 * - `slug` on export only: it is read-only, so a row read back skips it;
 * - one column per value slot: `code`, `code-locale`, `code-scope`, `code-locale-scope`;
 * - prices one column per currency, `code-USD`; metrics `code` and `code-unit`;
 * - multiselect option codes comma-separated; booleans 1/0.
 */
final class ProductRows
{
    private const array FIELDS = ['identifier', 'family', 'parent', 'owner', 'enabled', 'categories'];

    /**
     * Columns an export writes that an import must not send back.
     */
    private const array READ_ONLY = ['slug'];

    /**
     * @var Collection<string, AttributeModel>|null
     */
    private ?Collection $attributes = null;

    /**
     * A CSV row, keyed by header, as a product record.
     *
     * @param  array<string, string|null>  $row
     * @return array<string, mixed>
     */
    public function fromCsv(array $row): array
    {
        $record = [];

        foreach (self::FIELDS as $field) {
            $cell = trim((string) ($row[$field] ?? ''));

            if (! array_key_exists($field, $row)) {
                continue;
            }

            $record[$field] = match ($field) {
                'enabled' => $cell === '' ? true : in_array(strtolower($cell), ['1', 'true', 'yes'], true),
                'categories' => $this->list($cell),
                default => $cell === '' ? null : $cell,
            };
        }

        $values = [];
        $metrics = [];
        $prices = [];

        foreach ($row as $column => $cell) {
            if (in_array($column, self::FIELDS, true) || in_array($column, self::READ_ONLY, true)) {
                continue;
            }

            $parts = explode('-', (string) $column);
            $attribute = $this->attribute($parts[0]);

            if ($attribute === null) {
                continue;
            }

            $cell = (string) $cell;
            $rest = array_slice($parts, 1);

            if ($attribute->type === AttributeType::Metric && end($rest) === 'unit') {
                array_pop($rest);
                $metrics[$this->slotKey($attribute, $rest)]['unit'] = $cell;

                continue;
            }

            if ($attribute->type === AttributeType::Price && $rest !== [] && preg_match('/^[A-Z]{3}$/', (string) end($rest)) === 1) {
                $currency = (string) array_pop($rest);

                if ($cell !== '') {
                    $prices[$this->slotKey($attribute, $rest)][] = ['amount' => $cell, 'currency' => $currency];
                } else {
                    $prices[$this->slotKey($attribute, $rest)] ??= [];
                }

                continue;
            }

            [$locale, $scope] = $this->slot($attribute, $rest);

            if ($attribute->type === AttributeType::Metric) {
                $metrics[$this->slotKey($attribute, $rest)]['amount'] = $cell;

                continue;
            }

            $values[$attribute->code][] = ['locale' => $locale, 'scope' => $scope, 'data' => $this->data($attribute, $cell)];
        }

        foreach ($metrics as $key => $metric) {
            [$code, $locale, $scope] = explode('|', $key);

            $values[$code][] = [
                'locale' => $locale === '' ? null : $locale,
                'scope' => $scope === '' ? null : $scope,
                'data' => ($metric['amount'] ?? '') === '' ? null : ['amount' => $metric['amount'], 'unit' => $metric['unit'] ?? ''],
            ];
        }

        foreach ($prices as $key => $list) {
            [$code, $locale, $scope] = explode('|', $key);

            $values[$code][] = ['locale' => $locale === '' ? null : $locale, 'scope' => $scope === '' ? null : $scope, 'data' => $list === [] ? null : $list];
        }

        if ($values !== []) {
            $record['values'] = $values;
        }

        return $record;
    }

    /**
     * A product record as a CSV row keyed by column.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, string>
     */
    public function toCsv(array $record): array
    {
        $row = [
            'identifier' => (string) ($record['identifier'] ?? ''),
            'slug' => (string) ($record['slug'] ?? ''),
            'family' => (string) ($record['family'] ?? ''),
            'parent' => (string) ($record['parent'] ?? ''),
            'owner' => (string) ($record['owner'] ?? ''),
            'enabled' => ($record['enabled'] ?? true) ? '1' : '0',
            'categories' => implode(',', is_array($record['categories'] ?? null) ? $record['categories'] : []),
        ];

        foreach (is_array($record['values'] ?? null) ? $record['values'] : [] as $code => $slots) {
            $attribute = $this->attribute((string) $code);

            foreach (is_array($slots) ? $slots : [] as $slot) {
                $suffix = implode('-', array_filter([$slot['locale'] ?? null, $slot['scope'] ?? null], fn (mixed $part): bool => is_string($part) && $part !== ''));
                $column = $code.($suffix === '' ? '' : '-'.$suffix);
                $data = $slot['data'] ?? null;

                match (true) {
                    $attribute?->type === AttributeType::Metric && is_array($data) => $row += [
                        $column => (string) ($data['amount'] ?? ''),
                        $column.'-unit' => (string) ($data['unit'] ?? ''),
                    ],
                    $attribute?->type === AttributeType::Price && is_array($data) => $row += collect($data)
                        ->mapWithKeys(fn (mixed $price): array => is_array($price) ? [$column.'-'.($price['currency'] ?? '') => (string) ($price['amount'] ?? '')] : [])
                        ->all(),
                    is_array($data) => $row[$column] = implode(',', array_map('strval', $data)),
                    is_bool($data) => $row[$column] = $data ? '1' : '0',
                    default => $row[$column] = (string) $data,
                };
            }
        }

        return $row;
    }

    /**
     * @param  array<int, string>  $rest
     * @return array{0: string|null, 1: string|null}
     */
    private function slot(AttributeModel $attribute, array $rest): array
    {
        $locale = $attribute->is_localizable ? ($rest[0] ?? null) : null;
        $scope = $attribute->is_scopable ? ($rest[$attribute->is_localizable ? 1 : 0] ?? null) : null;

        return [$locale, $scope];
    }

    /**
     * @param  array<int, string>  $rest
     */
    private function slotKey(AttributeModel $attribute, array $rest): string
    {
        [$locale, $scope] = $this->slot($attribute, $rest);

        return $attribute->code.'|'.($locale ?? '').'|'.($scope ?? '');
    }

    private function data(AttributeModel $attribute, string $cell): mixed
    {
        if ($cell === '') {
            return null;
        }

        return match ($attribute->type) {
            AttributeType::Boolean => in_array(strtolower($cell), ['1', 'true', 'yes'], true),
            AttributeType::Number => is_numeric($cell) ? (int) $cell : $cell,
            AttributeType::Multiselect => $this->list($cell),
            default => $cell,
        };
    }

    /**
     * @return array<int, string>
     */
    private function list(string $cell): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $cell)), fn (string $item): bool => $item !== ''));
    }

    private function attribute(string $code): ?AttributeModel
    {
        $this->attributes ??= AttributeModel::query()->get()->keyBy('code');

        return $this->attributes->get($code);
    }
}
