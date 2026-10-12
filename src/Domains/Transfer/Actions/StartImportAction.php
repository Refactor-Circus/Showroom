<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Actions;

use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Showroom\Domains\Asset\Actions\CreateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Showroom\Domains\Transfer\Events\ImportStartedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ImportStartingActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Exceptions\ImpexMissingException;
use RefactorCircus\Showroom\Impex\ImpexIntegration;

final class StartImportAction
{
    public function __construct(private readonly ImpexIntegration $impex) {}

    /**
     * @throws ImpexMissingException|ValidationException
     */
    public function execute(StartImportData $data): RunModel
    {
        ImportStartingActionEvent::dispatch($data);

        $result = $this->perform($data);

        ImportStartedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(StartImportData $data): RunModel
    {
        if (! $this->impex->active()) {
            throw ImpexMissingException::make();
        }

        $asset = match (true) {
            $data->file !== null => app(CreateAssetAction::class)->execute(new CreateAssetData(file: $data->file)),
            $data->url !== null => app(CreateAssetAction::class)->execute(new CreateAssetData(url: $data->url)),
            default => AssetModel::query()->where('code', $data->asset)->firstOrFail(),
        };

        $format = $data->format ?? $this->formatOf($asset);

        return $this->impex->start(ImpexIntegration::IMPORT, [
            $asset->code,
            $format,
            $data->mode,
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function formatOf(AssetModel $asset): string
    {
        return match (strtolower(pathinfo($asset->filename, PATHINFO_EXTENSION))) {
            'csv' => 'csv',
            'jsonl', 'ndjson' => 'jsonl',
            default => throw ValidationException::withMessages([
                'format' => sprintf('Cannot tell the format of "%s"; send format csv or jsonl.', $asset->filename),
            ]),
        };
    }
}
