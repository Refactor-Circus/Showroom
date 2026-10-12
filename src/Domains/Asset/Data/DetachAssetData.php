<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;

/**
 * Input for DetachAssetAction: the record to unlink the asset from. Without
 * `role`, the asset is unlinked from it in every role.
 */
final readonly class DetachAssetData extends Data
{
    public function __construct(
        public string $type,
        public string $target,
        public ?string $role = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(AssetLinks::TYPES))],
            'target' => ['required', 'string', 'max:191'],
            'role' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
