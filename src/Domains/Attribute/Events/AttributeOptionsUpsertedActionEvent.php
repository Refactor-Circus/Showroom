<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsResult;

/**
 * A batch of attribute option records was upserted: what was created,
 * updated, left unchanged, skipped and refused.
 */
final class AttributeOptionsUpsertedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public UpsertAttributeOptionsResult $result,
    ) {}
}
