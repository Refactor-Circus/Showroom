<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * The Flag `deleting` Eloquent event.
 */
final class FlagDeletingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public FlagModel $flag) {}

    public function model(): Model
    {
        return $this->flag;
    }

    public function hook(): string
    {
        return 'deleting';
    }
}
