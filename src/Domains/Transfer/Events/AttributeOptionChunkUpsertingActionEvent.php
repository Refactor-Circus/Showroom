<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;

/**
 * One chunk of an attribute option upsert run is about to be written.
 * `start` is the position of its first record in the records sent.
 */
final class AttributeOptionChunkUpsertingActionEvent implements ActionStartingEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array{mode?: string, start?: int, records?: list<mixed>} */
        public array $chunk,
    ) {}
}
