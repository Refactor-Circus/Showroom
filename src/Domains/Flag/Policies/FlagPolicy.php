<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Showroom\Support\Policies\Policy;

class FlagPolicy extends Policy
{
    /**
     * Whether the user may change which flags a product or owner carries.
     * Checked on top of `update` on the record, and only when its flags
     * change. Ask with the class, and the record when there is one:
     * `allows('assign', [FlagModel::class, $product])`.
     */
    public function assign(Model $user, ?Model $subject = null): bool
    {
        return true;
    }
}
