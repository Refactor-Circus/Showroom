<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Http\Requests;

use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

abstract class FlagRequest extends Request
{
    protected function flag(): FlagModel
    {
        $flag = $this->route('flag');

        if (! $flag instanceof FlagModel) {
            abort(404);
        }

        return $flag;
    }
}
