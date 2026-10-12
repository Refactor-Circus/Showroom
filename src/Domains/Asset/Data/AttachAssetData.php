<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;

/**
 * Input for AttachAssetAction: a product (by identifier), a product model
 * or an owner (by code) to link the asset to, under a role. Attaching again
 * updates the order.
 */
final readonly class AttachAssetData extends Data
{
    public function __construct(
        public string $type,
        public string $target,
        public string $role = 'media',
        public int $sortOrder = 0,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(AssetLinks::TYPES))],
            'target' => ['required', 'string', 'max:191'],
            'role' => ['sometimes', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
