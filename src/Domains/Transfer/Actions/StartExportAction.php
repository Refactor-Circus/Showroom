<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Actions;

use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Showroom\Domains\Transfer\Events\ExportStartedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ExportStartingActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Exceptions\ImpexMissingException;
use RefactorCircus\Showroom\Impex\ImpexIntegration;

final class StartExportAction
{
    public function __construct(private readonly ImpexIntegration $impex) {}

    /**
     * @throws ImpexMissingException
     */
    public function execute(StartExportData $data): RunModel
    {
        ExportStartingActionEvent::dispatch($data);

        $result = $this->perform($data);

        ExportStartedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(StartExportData $data): RunModel
    {
        return $this->impex->start(ImpexIntegration::EXPORT, [
            $data->query(),
            $data->format,
            $data->code,
            $data->published,
        ]);
    }
}
