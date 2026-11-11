<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersResult;

/**
 * A batch of owner records was upserted: what was created, updated, left
 * unchanged, skipped and refused.
 */
final class OwnersUpsertedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public UpsertOwnersResult $result,
    ) {}
}
