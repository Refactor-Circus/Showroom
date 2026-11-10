<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;

/**
 * Input for UpsertOwnersAction: a batch of owner records in API shape
 * (`code`, `type`, `parent`, `labels`, `external_ids`, `flags`), as an ERP
 * or an import sends them.
 *
 * Each record is matched to a stored owner by its `code`, or, when `match`
 * names a source (an ERP), by its `external_ids[match]`. Records are
 * validated one by one, against CreateOwnerData's rules or
 * UpdateOwnerData's, by the action: one bad record never refuses the batch,
 * so only the batch's own options are checked here. The action expects at
 * most `showroom.impex.chunk` records; a caller with more sends them in
 * chunks.
 */
final readonly class UpsertOwnersData extends Data
{
    /**
     * @param  list<mixed>  $records  The owner records, each an object in API shape.
     * @param  string  $match  `code`, or the source whose external ids match records to owners.
     */
    public function __construct(
        public array $records,
        public UpsertMode $mode = UpsertMode::Upsert,
        public string $match = 'code',
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
            // `code` fits the pattern of a source name too.
            'match' => ['sometimes', 'string', 'regex:'.ExternalIds::SOURCE_PATTERN],
        ];
    }

    /**
     * Whether records are matched by a source's external ids rather than by
     * code.
     */
    public function matchesExternalIds(): bool
    {
        return $this->match !== 'code';
    }
}
