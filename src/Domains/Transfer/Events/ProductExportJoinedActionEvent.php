<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * An export's file was made the asset `asset`.
 */
final class ProductExportJoinedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $runId,
        public string $asset,
        public int $count,
        public string $format,
    ) {}
}
