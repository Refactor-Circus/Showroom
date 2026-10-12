<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Data;

use Illuminate\Http\UploadedFile;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for UpdateAssetAction. Sending `file`, `path` or `url` replaces the
 * file; the code and every link stay as they are. Labels left out are kept.
 */
final readonly class UpdateAssetData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     */
    public function __construct(
        public array|null|Missing $labels = new Missing,
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
            'code' => ['prohibited'],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ] + CreateAssetData::sourceRules(required: false);
    }
}
