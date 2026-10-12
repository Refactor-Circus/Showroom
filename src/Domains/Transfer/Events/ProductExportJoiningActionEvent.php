<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An export's part files are about to be stitched into one file and made
 * an asset.
 */
final class ProductExportJoiningActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $runId,
        public int $parts,
        public string $format,
        public string $code,
    ) {}
}
