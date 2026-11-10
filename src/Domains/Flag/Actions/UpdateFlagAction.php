<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Showroom\Domains\Flag\Data\UpdateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

final class UpdateFlagAction
{
    /**
     * @throws ValidationException
     */
    public function execute(FlagModel $flag, UpdateFlagData $data): FlagModel
    {
        FlagUpdatingActionEvent::dispatch($flag, $data);

        $result = $this->perform($flag, $data);

        FlagUpdatedActionEvent::dispatch($result);

        return $result;
    }

    private function perform(FlagModel $flag, UpdateFlagData $data): FlagModel
    {
        return DB::transaction(function () use ($flag, $data): FlagModel {
            if (! $data->appliesTo instanceof Missing) {
                $this->ensureUnassignedOutside($flag, $data->appliesTo);

                $flag->applies_to = $data->appliesTo;
            }

            if ($data->has('labels')) {
                $flag->labels = is_array($data->labels) ? $data->labels : [];
            }

            if (! $data->sortOrder instanceof Missing) {
                $flag->sort_order = $data->sortOrder;
            }

            $flag->save();

            return $flag;
        });
    }

    /**
     * A kind of record may leave `applies_to` only once the flag is no longer
     * assigned to any record of that kind.
     *
     * @param  list<string>  $appliesTo
     *
     * @throws ValidationException
     */
    private function ensureUnassignedOutside(FlagModel $flag, array $appliesTo): void
    {
        foreach (array_diff($flag->applies_to, $appliesTo) as $removed) {
            $subject = FlagSubject::tryFrom($removed);

            if ($subject === null) {
                continue;
            }

            $assigned = $flag->assignments($subject)->count();

            if ($assigned > 0) {
                throw ValidationException::withMessages([
                    'applies_to' => sprintf(
                        'Flag "%s" is assigned to %d %s(s). Remove it from them before taking %s out of applies_to.',
                        $flag->code,
                        $assigned,
                        $subject->value,
                        $subject->value,
                    ),
                ]);
            }
        }
    }
}
