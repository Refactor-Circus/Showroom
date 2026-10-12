<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use RefactorCircus\Showroom\Domains\Channel\Events\LocaleShowingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\LocaleShownActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

final class ShowLocaleAction
{
    public function execute(LocaleModel $locale): LocaleModel
    {
        LocaleShowingActionEvent::dispatch($locale);

        $result = $this->perform($locale);

        LocaleShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(LocaleModel $locale): LocaleModel
    {
        return $locale->load('channels');
    }
}
