<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Concerns;

use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/**
 * Parent types, shared by creating and updating an owner type.
 */
trait WritesOwnerTypes
{
    /**
     * @param  array<int, string>|null  $codes
     */
    private function setParentTypes(OwnerTypeModel $type, ?array $codes): void
    {
        $type->restricts_parents = $codes !== null;
        $type->save();

        $type->parentTypes()->sync(
            $codes === null ? [] : OwnerTypeModel::query()->whereIn('code', $codes)->pluck('id')->all(),
        );
    }
}
