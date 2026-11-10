<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;

/**
 * Input for UpsertProductsAction: a batch of product records in API shape,
 * as an export or a JSONL import line holds them.
 *
 * Each record is matched to a stored product by its `identifier`, or, when
 * `match` names an external system, by its `external_ids[match]`. Records are
 * validated one by one, against CreateProductData's rules or
 * UpdateProductData's, by the action: one bad record never refuses the
 * batch, so only the batch's own options are checked here.
 */
final readonly class UpsertProductsData extends Data
{
    /**
     * @param  list<mixed>  $records  The product records, each an object in API shape.
     * @param  string  $match  `identifier`, or the external system whose ids match records to products.
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
            // `identifier` fits the pattern of a system name too.
            'match' => ['sometimes', 'string', 'regex:'.ExternalIds::SYSTEM_PATTERN],
            'capture_changes' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Whether records are matched by an external system's ids rather than
     * by identifier.
     */
    public function matchesExternalIds(): bool
    {
        return $this->match !== 'identifier';
    }
}
