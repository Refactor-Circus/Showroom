<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

/**
 * Finds a product or an owner by its lookup key: the ULID `id` by default,
 * or the `slug`, from `showroom.lookup.products` and `showroom.lookup.owners`.
 * A call's own `by` (`?by=` over HTTP, a `by` tool argument) wins over the
 * config.
 *
 * Route binding, the dashboard's links, the MCP tools and the show, update,
 * delete, transition, versions and revert requests all find their record
 * here, so every surface answers to the same key.
 */
final class RecordLookup
{
    /**
     * The records found by a lookup key, by their `showroom.lookup` key.
     *
     * @var array<string, class-string<ProductModel|OwnerModel>>
     */
    private const array SUBJECTS = ['products' => ProductModel::class, 'owners' => OwnerModel::class];

    /**
     * The record of a subject (`products` or `owners`) whose lookup key
     * holds the value: the `by` key when one is given, else the configured
     * one.
     *
     * @throws ModelNotFoundException
     */
    public function resolve(string $subject, string $value, ?LookupKey $by = null): ProductModel|OwnerModel
    {
        return $this->find(self::model($subject), $by ?? $this->key($subject), $value);
    }

    /**
     * The product whose lookup key holds the value.
     *
     * @throws ModelNotFoundException
     */
    public function product(string $value, ?LookupKey $by = null): ProductModel
    {
        return $this->find(ProductModel::class, $by ?? $this->key('products'), $value);
    }

    /**
     * The owner whose lookup key holds the value.
     *
     * @throws ModelNotFoundException
     */
    public function owner(string $value, ?LookupKey $by = null): OwnerModel
    {
        return $this->find(OwnerModel::class, $by ?? $this->key('owners'), $value);
    }

    /**
     * The key a subject is found by when a call names none, from
     * `showroom.lookup.{subject}` (`id` when the key is absent).
     *
     * @throws InvalidArgumentException when the configured value is not a lookup key.
     */
    public function key(string $subject): LookupKey
    {
        self::model($subject);

        $configured = config('showroom.lookup.'.$subject) ?? LookupKey::Id->value;
        $key = is_string($configured) ? LookupKey::tryFrom($configured) : null;

        if ($key === null) {
            throw new InvalidArgumentException(sprintf(
                'The [showroom.lookup.%s] config value must be one of [%s]; [%s] given. Identifiers and owner codes are not lookup keys.',
                $subject,
                implode(', ', array_column(LookupKey::cases(), 'value')),
                is_scalar($configured) ? (string) $configured : get_debug_type($configured),
            ));
        }

        return $key;
    }

    /**
     * Check every configured lookup key, so a bad value fails when the app
     * boots rather than on the first request.
     *
     * @throws InvalidArgumentException
     */
    public function assertConfigured(): void
    {
        foreach (array_keys(self::SUBJECTS) as $subject) {
            $this->key($subject);
        }
    }

    /**
     * The key a call asked for with `by`, or null when it sent none.
     *
     * @throws ValidationException at `by` when it is not a lookup key.
     */
    public static function by(mixed $by): ?LookupKey
    {
        $validated = Validator::validate(['by' => $by], self::rules());

        return is_string($validated['by'] ?? null) && $validated['by'] !== '' ? LookupKey::from($validated['by']) : null;
    }

    /**
     * The rule for a call's `by`: optional, and one of the lookup keys.
     *
     * @return array{by: list<mixed>}
     */
    public static function rules(): array
    {
        return ['by' => ['sometimes', 'nullable', Rule::enum(LookupKey::class)]];
    }

    /**
     * @template TModel of ProductModel|OwnerModel
     *
     * @param  class-string<TModel>  $model
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    private function find(string $model, LookupKey $key, string $value): Model
    {
        // A value that cannot be a ULID matches no id: no query needed.
        if ($key === LookupKey::Id && ! Str::isUlid($value)) {
            throw (new ModelNotFoundException)->setModel($model, [$value]);
        }

        return $model::query()->where($key->value, $value)->firstOrFail();
    }

    /**
     * @return class-string<ProductModel|OwnerModel>
     *
     * @throws InvalidArgumentException
     */
    private static function model(string $subject): string
    {
        return self::SUBJECTS[$subject] ?? throw new InvalidArgumentException(sprintf(
            'Only products and owners have lookup keys, not [%s].',
            $subject,
        ));
    }
}
