<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Actions;

use RefactorCircus\Showroom\Domains\Flag\Events\FlagShowingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagShownActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

final class ShowFlagAction
{
    public function execute(FlagModel $flag): FlagModel
    {
        FlagShowingActionEvent::dispatch($flag);

        $result = $this->perform($flag);

        FlagShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(FlagModel $flag): FlagModel
    {
        return $flag;
    }
}
