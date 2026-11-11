<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/**
 * Input for CreateOwnerAction: the owner's code and type, where it sits,
 * its labels and whether it is enabled.
 */
final readonly class CreateOwnerData extends Data
{
    /**
     * @param  array<string, string|null>|null  $labels
     * @param  array<string, string|null>|null  $externalIds  The owner's ids in other systems, {source: id}.
     * @param  list<string>|null  $flags  Codes of the owner's own flags.
     * @param  bool  $enabled  False switches off every product beneath it.
     */
    public function __construct(
        public string $code,
        public string $type,
        public ?string $parent = null,
        public ?array $labels = null,
        public ?array $externalIds = null,
        public ?array $flags = null,
        public bool $enabled = true,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/', Rule::unique(OwnerModel::class, 'code')],
            // Set from the label or the code; read-only.
            'slug' => ['prohibited'],
            // Own flags plus every ancestor's; read-only.
            'effective_flags' => ['missing'],
            // When each source last synced its id; read-only.
            'external_ids_synced_at' => ['missing'],
            'type' => ['required', 'string', Rule::exists(OwnerTypeModel::class, 'code')],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
        ] + ExternalIds::rules() + Flags::rules(FlagSubject::Owner);
    }
}
