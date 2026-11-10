<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Events;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * Flags were listed.
 */
final class FlagsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  CursorPaginator<int, FlagModel>  $flags
     */
    public function __construct(
        public CursorPaginator $flags,
    ) {}
}
