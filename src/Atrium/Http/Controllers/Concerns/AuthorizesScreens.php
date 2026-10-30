<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Atrium\Support\ScreenAccess;

/**
 * For Showroom's screen controllers: refuse with the same policy check the
 * JSON API and MCP tools make, and as they do, a refused guest gets 401 and a
 * refused signed-in user 403. Atrium's own concern answers 403 to both.
 */
trait AuthorizesScreens
{
    /**
     * @param  Model|class-string<Model>  $subject
     * @param  array<int, mixed>  $arguments  Further policy arguments, after the subject.
     */
    protected function authorizeScreen(string $ability, Model|string $subject, array $arguments = []): void
    {
        $this->refuseUnless(ScreenAccess::allows('showroom', $ability, $subject, arguments: $arguments));
    }

    protected function refuseUnless(bool $allowed): void
    {
        abort_unless($allowed, request()->user() === null ? 401 : 403);
    }
}
