<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;

/**
 * One imported product row was written, or skipped: `result` is `created`,
 * `updated` or `skipped`.
 */
final class ProductRowImportedActionEvent implements ActionFinishedEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $identifier,
        public string $result,
    ) {}
}
