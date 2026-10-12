<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;

/**
 * An attribute group is about to be updated.
 */
final class AttributeGroupUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AttributeGroupModel $group,
        public UpdateAttributeGroupData $data,
    ) {}
}
