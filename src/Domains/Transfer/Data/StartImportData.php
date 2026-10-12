<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;

/**
 * Input for StartImportAction. The file: an existing `asset`, or a `file`
 * upload or `url`, which becomes one first. Without a `format`, the file's
 * extension tells it.
 */
final readonly class StartImportData extends Data
{
    public function __construct(
        public ?string $asset = null,
        public ?UploadedFile $file = null,
        public ?string $url = null,
        public ?string $format = null,
        public string $mode = 'upsert',
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'asset' => ['required_without_all:file,url', 'nullable', 'string', Rule::exists(AssetModel::class, 'code')],
            'file' => ['sometimes', 'file'],
            'url' => ['sometimes', 'string', 'url:http,https'],
            'format' => ['sometimes', 'nullable', Rule::in(['csv', 'jsonl'])],
            'mode' => ['sometimes', Rule::in(['create', 'update', 'upsert'])],
        ];
    }
}
