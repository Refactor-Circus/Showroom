<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Requests;

use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

abstract class FlagRequest extends Request
{
    protected function flag(): FlagModel
    {
        return FlagModel::query()->where('code', $this->get('flag'))->firstOrFail();
    }
}
