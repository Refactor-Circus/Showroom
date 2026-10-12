<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Channel\Events\LocaleDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Events\LocaleDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Exceptions\ModelInUseException;
use RefactorCircus\Showroom\Jobs\PurgeValueSlots;

final class DeleteLocaleAction
{
    public function execute(LocaleModel $locale): LocaleModel
    {
        LocaleDeletingActionEvent::dispatch($locale);

        $result = $this->perform($locale);

        LocaleDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * Refused while a channel publishes in the locale; otherwise the values
     * written in it are purged by a queued job.
     */
    private function perform(LocaleModel $locale): LocaleModel
    {
        return DB::transaction(function () use ($locale): LocaleModel {
            /** @var array<int, string> $channels */
            $channels = $locale->channels()->pluck('code')->all();

            if ($channels !== []) {
                throw ModelInUseException::localeInChannels($locale->code, $channels);
            }

            $locale->delete();

            PurgeValueSlots::dispatch(locale: $locale->code)->afterCommit();

            return $locale;
        });
    }
}
