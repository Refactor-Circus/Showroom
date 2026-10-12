<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;

/**
 * Input for CreateAssetAction: exactly one of `file`, `path` or `url`. The
 * code is generated from the file name when omitted.
 */
final readonly class CreateAssetData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     */
    public function __construct(
        public ?string $code = null,
        public ?array $labels = null,
        public ?UploadedFile $file = null,
        public ?string $path = null,
        public ?string $url = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['sometimes', 'nullable', 'string', 'max:191', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', Rule::unique(AssetModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ] + self::sourceRules(required: true);
    }

    /**
     * Where the file comes from, shared with UpdateAssetData: exactly one
     * source - a multipart upload, a path already on the asset disk, or a
     * URL to fetch.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function sourceRules(bool $required): array
    {
        $file = ['file', 'max:'.config('showroom.media.max_kilobytes', 51200)];

        /** @var array<int, string>|null $types */
        $types = config('showroom.media.mime_types');

        if (is_array($types) && $types !== []) {
            $file[] = 'mimetypes:'.implode(',', $types);
        }

        $one = fn (string $others): array => $required ? ['required_without_all:'.$others, 'prohibits:'.$others] : ['prohibits:'.$others];

        return [
            'file' => [...$one('path,url'), ...$file],
            'path' => [...$one('file,url'), 'string', 'max:1024'],
            'url' => [...$one('file,path'), 'string', 'url:http,https', 'max:2048'],
        ];
    }
}
