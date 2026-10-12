<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Actions;

use RefactorCircus\Showroom\Domains\Asset\Events\AssetShowingActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetShownActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;

final class ShowAssetAction
{
    public function execute(AssetModel $asset): AssetModel
    {
        AssetShowingActionEvent::dispatch($asset);

        $result = $this->perform($asset);

        AssetShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AssetModel $asset): AssetModel
    {
        return $asset->load(['products', 'productModels', 'owners']);
    }
}
