<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * The Flag `replicating` Eloquent event.
 */
final class FlagReplicatingEvent implements ModelLifecycleEvent
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
        return 'replicating';
    }
}
