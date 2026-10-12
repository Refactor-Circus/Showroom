<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Showroom\Domains\Asset\Data\AttachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;

/**
 * An asset is about to be linked to a record.
 */
final class AssetAttachingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AssetModel $asset,
        public AttachAssetData $data,
    ) {}
}
