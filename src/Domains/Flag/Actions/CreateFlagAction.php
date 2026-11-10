<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Flag\Data\CreateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

final class CreateFlagAction
{
    public function execute(CreateFlagData $data): FlagModel
    {
        FlagCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        FlagCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateFlagData $data): FlagModel
    {
        return DB::transaction(fn (): FlagModel => FlagModel::query()->create([
            'code' => $data->code,
            'labels' => $data->labels ?? [],
            'applies_to' => $data->appliesTo,
            'sort_order' => $data->sortOrder,
        ]));
    }
}
