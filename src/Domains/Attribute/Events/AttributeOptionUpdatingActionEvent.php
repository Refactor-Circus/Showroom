<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpdateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;

/**
 * An attribute option is about to be updated.
 */
final class AttributeOptionUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AttributeOptionModel $option,
        public UpdateAttributeOptionData $data,
    ) {}
}
