<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Data\ListAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * The options of an attribute are about to be listed.
 */
final class AttributeOptionsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AttributeModel $attribute,
        public ListAttributeOptionsData $filters,
    ) {}
}
