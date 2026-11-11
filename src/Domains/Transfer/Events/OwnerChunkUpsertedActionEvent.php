<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;

/**
 * One chunk of an owner upsert run was written: its counts, and each failed
 * record by its position in the records sent.
 */
final class OwnerChunkUpsertedActionEvent implements ActionFinishedEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        /** @var array{total: int, created: int, updated: int, unchanged: int, skipped: int, failed: int, failures: list<array{index: int, code: string|null, errors: array<string, list<string>>}>} */
        public array $result,
    ) {}
}
