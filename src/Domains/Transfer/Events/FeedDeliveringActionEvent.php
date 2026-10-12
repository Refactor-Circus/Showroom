<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * A feed file is about to be sent to its destination.
 */
final class FeedDeliveringActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $asset,
        public string $url,
        public string $channel,
        public string $runId,
        public ?string $through,
    ) {}
}
