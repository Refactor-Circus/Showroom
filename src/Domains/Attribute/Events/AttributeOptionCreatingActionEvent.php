<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeOptionData;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * An attribute option is about to be created.
 */
final class AttributeOptionCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AttributeModel $attribute,
        public CreateAttributeOptionData $data,
    ) {}
}
