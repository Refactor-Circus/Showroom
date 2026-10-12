<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Asset\Data\AttachAssetData;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetAttachedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetAttachingActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetLinks;

final class AttachAssetAction
{
    public function __construct(private readonly AssetLinks $links) {}

    /**
     * @throws ValidationException
     */
    public function execute(AssetModel $asset, AttachAssetData $data): AssetModel
    {
        AssetAttachingActionEvent::dispatch($asset, $data);

        $result = $this->perform($asset, $data);

        AssetAttachedActionEvent::dispatch($result, $data);

        return $result;
    }

    private function perform(AssetModel $asset, AttachAssetData $data): AssetModel
    {
        return DB::transaction(function () use ($asset, $data): AssetModel {
            $record = $this->links->resolve($data->type, $data->target);

            $this->links->attach($asset, $record, $data->role, $data->sortOrder);

            return $asset->load(['products', 'productModels', 'owners']);
        });
    }
}
