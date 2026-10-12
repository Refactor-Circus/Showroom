<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Asset\Concerns\StoresAssetFiles;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Asset\Services\AssetStorage;

final class CreateAssetAction
{
    use StoresAssetFiles;

    public function __construct(private readonly AssetStorage $storage) {}

    /**
     * @throws ValidationException
     */
    public function execute(CreateAssetData $data): AssetModel
    {
        AssetCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        AssetCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateAssetData $data): AssetModel
    {
        $stored = $this->storeSource($this->storage, $data->file, $data->path, $data->url);

        if ($stored === null) {
            throw ValidationException::withMessages(['file' => 'Send a file, a path on the asset disk, or a URL.']);
        }

        // The file is written before the transaction, so a slow upload or
        // download holds no locks.
        return DB::transaction(function () use ($data, $stored): AssetModel {
            // A record that is rolled back - here or by a surrounding
            // transaction - must not leave its file behind. An adopted file
            // is the caller's.
            if ($data->path === null) {
                DB::afterRollBack(fn () => $this->storage->discard($stored));
            }

            return AssetModel::query()->create([
                'code' => $data->code ?? $this->codeFor($stored->filename),
                'labels' => $data->labels ?? [],
                ...$stored->toAttributes(),
            ]);
        });
    }

    private function codeFor(string $filename): string
    {
        $base = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'asset';

        do {
            $code = $base.'-'.Str::lower(Str::random(6));
        } while (AssetModel::query()->where('code', $code)->exists());

        return $code;
    }
}
