<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Association\Data\UpdateAssociationTypeData;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;

/**
 * An association type is about to be updated.
 */
final class AssociationTypeUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AssociationTypeModel $associationType,
        public UpdateAssociationTypeData $data,
    ) {}
}
