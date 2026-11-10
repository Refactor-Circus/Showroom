<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\ExternalId\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;

/**
 * The ExternalId `deleting` Eloquent event.
 */
final class ExternalIdDeletingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ExternalIdModel $externalId) {}

    public function model(): Model
    {
        return $this->externalId;
    }

    public function hook(): string
    {
        return 'deleting';
    }
}
