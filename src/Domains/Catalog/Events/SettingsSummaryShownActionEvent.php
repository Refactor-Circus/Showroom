<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Catalog\Actions\ShowSettingsSummaryAction;

/**
 * The settings summary was read.
 *
 * @phpstan-import-type Summary from ShowSettingsSummaryAction
 */
final class SettingsSummaryShownActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  Summary  $summary
     */
    public function __construct(
        public array $summary,
    ) {}
}
