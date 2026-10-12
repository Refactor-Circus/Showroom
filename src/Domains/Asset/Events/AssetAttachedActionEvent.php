<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Asset\Data\AttachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;

/**
 * An asset was linked to a record.
 */
final class AssetAttachedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AssetModel $asset,
        public AttachAssetData $data,
    ) {}
}
