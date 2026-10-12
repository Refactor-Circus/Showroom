<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Asset\Data\DetachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetDetachedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetDetachingActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;

final class DetachAssetAction
{
    public function __construct(private readonly AssetLinks $links) {}

    /**
     * @throws ValidationException
     */
    public function execute(AssetModel $asset, DetachAssetData $data): AssetModel
    {
        AssetDetachingActionEvent::dispatch($asset, $data);

        $result = $this->perform($asset, $data);

        AssetDetachedActionEvent::dispatch($result, $data);

        return $result;
    }

    private function perform(AssetModel $asset, DetachAssetData $data): AssetModel
    {
        return DB::transaction(function () use ($asset, $data): AssetModel {
            $record = $this->links->resolve($data->type, $data->target);

            $this->links->detach($asset, $record, $data->role);

            return $asset->load(['products', 'productModels', 'owners']);
        });
    }
}
