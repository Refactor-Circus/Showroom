<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;

/**
 * An export is about to write the products its search matches as part
 * files - again on each resume.
 */
final class ProductPagesExportingActionEvent implements ActionStartingEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array<string, mixed> */
        public array $query,
        public string $runId,
        public bool $published,
    ) {}
}
