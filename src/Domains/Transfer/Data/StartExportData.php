<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Data;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Attributes\ListOf;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Product\Data\ListProductsData;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Search\Data\Complete;
use RefactorCircus\Showroom\Domains\Search\Data\Filter;

/**
 * Input for StartExportAction. Any product search filter narrows the export;
 * `scope` and `locales` narrow the values written. The file becomes an
 * asset - its code is in the run's result.
 */
final readonly class StartExportData extends Data
{
    /**
     * @param  list<Filter>  $filters
     * @param  list<string>|null  $locales
     */
    public function __construct(
        public ?string $search = null,
        public ?string $family = null,
        public ?bool $enabled = null,
        public ?string $parent = null,
        public ?string $owner = null,
        public ?string $category = null,
        public ?ProductStatus $status = null,
        public ?string $updatedSince = null,
        public ?Complete $complete = null,
        #[ListOf(Filter::class)]
        public array $filters = [],
        public ?string $scope = null,
        public ?array $locales = null,
        public string $format = 'jsonl',
        public ?string $code = null,
        public bool $published = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return Arr::except(ListProductsData::rules(), ['sort', 'page', 'per_page', 'facets', 'facets.*']) + [
            'format' => ['sometimes', Rule::in(['jsonl', 'csv'])],
            'code' => ['sometimes', 'nullable', 'string', 'max:191', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', Rule::unique(AssetModel::class, 'code')],
            // Write each product's live version, and leave out unpublished ones.
            'published' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The search the export runs, as ListProductsData input: what the run
     * stores and each page replays. Filters left out stay out; the value
     * filters and completeness condition are stored as their `toArray()`.
     *
     * @return array<string, mixed>
     */
    public function query(): array
    {
        $query = ['status' => $this->status?->value] + Arr::except($this->toArray(), ['format', 'code', 'published']);

        return array_filter($query, fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
