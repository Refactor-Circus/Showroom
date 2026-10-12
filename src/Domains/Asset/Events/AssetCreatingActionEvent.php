<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;

/**
 * An asset is about to be created.
 */
final class AssetCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CreateAssetData $data,
    ) {}
}
