<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateLocaleData;
use RefactorCircus\Showroom\Domains\Channel\Events\LocaleUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\LocaleUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

final class UpdateLocaleAction
{
    public function execute(LocaleModel $locale, UpdateLocaleData $data): LocaleModel
    {
        LocaleUpdatingActionEvent::dispatch($locale, $data);

        $result = $this->perform($locale, $data);

        LocaleUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(LocaleModel $locale, UpdateLocaleData $data): LocaleModel
    {
        return DB::transaction(function () use ($locale, $data): LocaleModel {
            if ($data->has('labels')) {
                $locale->labels = is_array($data->labels) ? $data->labels : [];
                $locale->save();
            }

            return $locale;
        });
    }
}
