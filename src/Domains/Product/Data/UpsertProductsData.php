<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Data;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;

/**
 * Input for UpsertProductsAction: a batch of product records in API shape,
 * as an export or a JSONL import line holds them.
 *
 * Each record is matched to a stored product by its `identifier`, or, when
 * `match` names a source (an ERP, a shop), by its `external_ids[match]`.
 * Records are validated one by one, against CreateProductData's rules or
 * UpdateProductData's, by the action: one bad record never refuses the
 * batch, so only the batch's own options are checked here.
 */
final readonly class UpsertProductsData extends Data
{
    /**
     * @param  list<mixed>  $records  The product records, each an object in API shape.
     * @param  string  $match  `identifier`, or the source whose external ids match records to products.
     * @param  bool  $captureChanges  Off: the writes are not recorded for product webhooks.
     */
    public function __construct(
        public array $records,
        public UpsertMode $mode = UpsertMode::Upsert,
        public string $match = 'identifier',
        public bool $captureChanges = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            // Each record is validated by the action, so it can fail alone.
            'records' => ['present', 'array', 'list'],
            'mode' => ['sometimes', Rule::enum(UpsertMode::class)],
            // `identifier` fits the pattern of a source name too.
            'match' => ['sometimes', 'string', 'regex:'.ExternalIds::SOURCE_PATTERN],
            'capture_changes' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The same as from(['records' => $records, ...$options]), without
     * passing the records through the validator, which copies every nested
     * array it is given: tens of megabytes for a thousand records with their
     * values. The records are only checked to be a list, as `records` asks;
     * the action validates each one.
     *
     * @param  array<array-key, mixed>  $records
     * @param  array<string, mixed>  $options  `mode`, `match`, `capture_changes`, validated as from() does.
     *
     * @throws ValidationException
     */
    public static function fromRecords(array $records, array $options = []): self
    {
        if (! array_is_list($records)) {
            // Refused with from()'s own error.
            return self::from([...$options, 'records' => $records]);
        }

        /** @var array<string, mixed> $validated */
        $validated = Validator::make([...$options, 'records' => []], self::rules())->validate();
        $validated['records'] = $records;

        return self::fromValidated($validated);
    }

    /**
     * Whether records are matched by a source's external ids rather than by
     * identifier.
     */
    public function matchesExternalIds(): bool
    {
        return $this->match !== 'identifier';
    }
}
