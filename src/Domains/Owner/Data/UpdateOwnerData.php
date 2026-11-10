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
 * Input for UpdateOwnerAction: the labels and the parent. Sending `parent`
 * moves the owner, with everything beneath it; null makes it a root. Fields
 * left out are kept.
 */
final readonly class UpdateOwnerData extends Data
{
    /**
     * @param  array<string, string|null>|null|Missing  $labels
     * @param  array<string, string|null>|Missing  $externalIds  Only the systems sent change; null removes one.
     * @param  list<string>|Missing  $flags  Codes of the owner's own flags; replaces the set.
     */
    public function __construct(
        public string|null|Missing $parent = new Missing,
        public array|null|Missing $labels = new Missing,
        public array|Missing $externalIds = new Missing,
        public array|Missing $flags = new Missing,
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
            'type' => ['prohibited'],
            'parent' => ['sometimes', 'nullable', 'string', Rule::exists(OwnerModel::class, 'code')],
            'labels' => ['sometimes', 'nullable', 'array'],
            'labels.*' => ['nullable', 'string', 'max:255'],
        ] + ExternalIds::rules() + Flags::rules(FlagSubject::Owner);
    }
}
