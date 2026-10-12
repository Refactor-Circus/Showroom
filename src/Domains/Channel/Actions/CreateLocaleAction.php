<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateLocaleData;
use RefactorCircus\Showroom\Domains\Channel\Events\LocaleCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\LocaleCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

final class CreateLocaleAction
{
    public function execute(CreateLocaleData $data): LocaleModel
    {
        LocaleCreatingActionEvent::dispatch($data);

        $result = $this->perform($data);

        LocaleCreatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(CreateLocaleData $data): LocaleModel
    {
        return DB::transaction(function () use ($data): LocaleModel {
            return LocaleModel::query()->create([
                'code' => $data->code,
                'labels' => $data->labels ?? [],
            ]);
        });
    }
}
