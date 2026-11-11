<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;

/**
 * Input for UpsertAttributeOptionsAction: a batch of option records
 * (`attribute`, `code`, `labels`, `sort_order`), as a catalog sync or an
 * import sends them. `attribute` is the code of a select or multiselect
 * attribute, so one batch can fill the options of many attributes.
 *
 * Each record is matched to a stored option by its attribute and its code.
 * Records are validated one by one, against CreateAttributeOptionData's
 * rules or UpdateAttributeOptionData's, by the action: one bad record never
 * refuses the batch, so only the batch's own options are checked here. The
 * action expects at most `showroom.impex.chunk` records; a caller with more
 * sends them in chunks.
 */
final readonly class UpsertAttributeOptionsData extends Data
{
    /**
     * @param  list<mixed>  $records  The option records, each an object.
     */
    public function __construct(
        public array $records,
        public UpsertMode $mode = UpsertMode::Upsert,
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
        ];
    }
}
