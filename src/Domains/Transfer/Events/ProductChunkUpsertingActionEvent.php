<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;

/**
 * One chunk of an import or upsert run is about to be written. `start` is
 * the source row of its first record.
 */
final class ProductChunkUpsertingActionEvent implements ActionStartingEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array{mode?: string, capture_changes?: bool, start?: int, records?: list<mixed>} */
        public array $chunk,
    ) {}
}
