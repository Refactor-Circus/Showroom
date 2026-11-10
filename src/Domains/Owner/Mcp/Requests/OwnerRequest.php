<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Support\RecordLookup;

abstract class OwnerRequest extends Request
{
    /**
     * The owner the `owner` argument names, by the configured lookup key or
     * the call's `by`. An invalid `by` is a validation error, raised before
     * the call is authorized or reaches its action.
     */
    protected function owner(): OwnerModel
    {
        $owner = $this->get('owner');

        return app(RecordLookup::class)->owner(is_scalar($owner) ? (string) $owner : '', RecordLookup::by($this->get('by')));
    }
}
