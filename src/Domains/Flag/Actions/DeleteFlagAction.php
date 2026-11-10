<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

final class DeleteFlagAction
{
    /**
     * @throws ValidationException
     */
    public function execute(FlagModel $flag): FlagModel
    {
        FlagDeletingActionEvent::dispatch($flag);

        $result = $this->perform($flag);

        FlagDeletedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * A flag still assigned to a product or owner stays: deleting it would
     * silently lift the restriction it carries.
     */
    private function perform(FlagModel $flag): FlagModel
    {
        return DB::transaction(function () use ($flag): FlagModel {
            $assigned = $flag->assignments()->count();

            if ($assigned > 0) {
                throw ValidationException::withMessages([
                    'flag' => sprintf('Flag "%s" is assigned to %d record(s). Remove it from them first.', $flag->code, $assigned),
                ]);
            }

            $flag->delete();

            return $flag;
        });
    }
}
