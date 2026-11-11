<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;

/**
 * Input for UpdateOwnerAction: the labels, the parent and whether it is
 * enabled. Sending `parent` moves the owner, with everything beneath it;
 * null makes it a root. Fields left out are kept.
 */
final readonly class UpdateOwnerData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  array<string, string|null>|Missing  $externalIds  Only the sources sent change; null removes one.
     * @param  list<string>|Missing  $flags  Codes of the owner's own flags; replaces the set.
     * @param  bool|Missing  $enabled  False switches off every product beneath it.
     */
    public function __construct(
        public string|null|Missing $parent = new Missing,
        public array|null|Missing $labels = new Missing,
        public array|Missing $externalIds = new Missing,
        public array|Missing $flags = new Missing,
        public bool|Missing $enabled = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'slug' => ['prohibited'],
            'effective_flags' => ['missing'],
            'external_ids_synced_at' => ['missing'],
            'type' => ['prohibited'],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
        ] + ExternalIds::rules() + Flags::rules(FlagSubject::Owner);
    }
}
