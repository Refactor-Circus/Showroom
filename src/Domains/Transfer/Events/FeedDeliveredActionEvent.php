<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;

/**
 * A feed file was delivered; `status` is the destination's answer, when it
 * gave one.
 */
final class FeedDeliveredActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $asset,
        public string $runId,
        public ?int $status,
    ) {}
}
