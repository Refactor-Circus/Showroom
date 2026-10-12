<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Asset\Concerns\StoresAssetFiles;
use RefactorCircus\Showroom\Domains\Asset\Data\UpdateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetStorage;

final class UpdateAssetAction
{
    use StoresAssetFiles;

    public function __construct(private readonly AssetStorage $storage) {}

    /**
     * @throws ValidationException
     */
    public function execute(AssetModel $asset, UpdateAssetData $data): AssetModel
    {
        AssetUpdatingActionEvent::dispatch($asset, $data);

        $result = $this->perform($asset, $data);

        AssetUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(AssetModel $asset, UpdateAssetData $data): AssetModel
    {
        // The new file is written before the transaction, so a slow upload or
        // download holds no locks.
        $stored = $this->storeSource($this->storage, $data->file, $data->path, $data->url);

        return DB::transaction(function () use ($asset, $data, $stored): AssetModel {
            if ($data->has('labels')) {
                $asset->labels = is_array($data->labels) ? $data->labels : [];
            }

            if ($stored === null) {
                $asset->save();

                return $asset;
            }

            // A rollback - here or by a surrounding transaction - removes the
            // file just written (an adopted file is the caller's) and keeps
            // the old one, which the record still points at.
            if ($data->path === null) {
                DB::afterRollBack(fn () => $this->storage->discard($stored));
            }

            $previous = $asset->replicate();
            $asset->fill($stored->toAttributes());
            $asset->save();

            if ($previous->path !== $asset->path || $previous->disk !== $asset->disk) {
                DB::afterCommit(fn () => $this->storage->delete($previous));
            }

            return $asset;
        });
    }
}
