<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;

/**
 * An owner is about to be updated.
 */
final class OwnerUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OwnerModel $owner,
        public UpdateOwnerData $data,
    ) {}
}
