<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Family\Data\UpdateFamilyData;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;

/**
 * A family is about to be updated.
 */
final class FamilyUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FamilyModel $family,
        public UpdateFamilyData $data,
    ) {}
}
