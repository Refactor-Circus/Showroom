<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyVariantData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;

/**
 * A family variant is about to be updated.
 */
final class FamilyVariantUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FamilyVariantModel $familyVariant,
        public UpdateFamilyVariantData $data,
    ) {}
}
