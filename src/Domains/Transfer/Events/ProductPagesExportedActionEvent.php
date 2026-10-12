<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;

/**
 * An export wrote its part files: all of them when `complete`, or as many
 * as it could before yielding.
 */
final class ProductPagesExportedActionEvent implements ActionFinishedEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $runId,
        public int $parts,
        public int $count,
        public bool $complete,
    ) {}
}
