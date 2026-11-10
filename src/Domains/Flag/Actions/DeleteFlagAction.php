<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;

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
     * silently lift the restriction it carries. Assignments left by a record
     * deleted outside the Actions hold nothing back, and go with the flag.
     */
    private function perform(FlagModel $flag): FlagModel
    {
        return DB::transaction(function () use ($flag): FlagModel {
            $assigned = app(Flags::class)->holders($flag);

            if ($assigned > 0) {
                throw ValidationException::withMessages([
                    'flag' => sprintf('Flag "%s" is assigned to %d record(s). Remove it from them first.', $flag->code, $assigned),
                ]);
            }

            $flag->assignments()->delete();
            $flag->delete();

            return $flag;
        });
    }
}
