<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Asset\Data\DetachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;

/**
 * An asset was unlinked from a record.
 */
final class AssetDetachedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AssetModel $asset,
        public DetachAssetData $data,
    ) {}
}
