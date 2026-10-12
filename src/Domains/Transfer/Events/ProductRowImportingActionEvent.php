<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;

/**
 * One imported product row is about to be written.
 */
final class ProductRowImportingActionEvent implements ActionStartingEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array{mode?: string, record?: array<string, mixed>} */
        public array $item,
    ) {}
}
