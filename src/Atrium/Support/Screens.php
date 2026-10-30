<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Atrium\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Showroom\Atrium\ScreenAccess;

/**
 * Showroom's screens as declared to Atrium's screen extensions registry: the
 * standard slots for their kind, their record, and who may see a
 * `permission()` extension.
 *
 * Extensions' permissions are policy abilities asked through Showroom's own
 * ScreenAccess, so with `showroom.authorization` off every one is granted.
 * They are asked about the record (the screen's, or a row's) when
 * `showroom.policies` covers its class, and otherwise about the screen's
 * subject class, so a record from another package never reaches the
 * Authorizer.
 */
final class Screens
{
    /**
     * A detail screen, such as a record's page or a create form.
     *
     * @param  class-string<Model>  $subject  The class asked about while the screen has no record.
     */
    public static function detail(string $key, ?Model $record, string $subject): Screen
    {
        return self::access(Screen::standard($key, Screen::DETAIL)->record($record), $subject);
    }

    /**
     * A list screen, whose rows carry the `row.actions` slot.
     *
     * @param  class-string<Model>  $subject  The class asked about for rows `showroom.policies` does not cover.
     */
    public static function list(string $key, string $subject): Screen
    {
        return self::access(Screen::standard($key, Screen::LIST), $subject);
    }

    /**
     * @param  class-string<Model>  $subject
     */
    private static function access(Screen $screen, string $subject): Screen
    {
        return $screen->access(fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allows(
            $permission,
            $record !== null && self::covered($record) ? $record : $subject,
            $request,
        ));
    }

    /**
     * Whether a policy in `showroom.policies` answers for the record's class.
     */
    private static function covered(Model $record): bool
    {
        $policies = config('showroom.policies', []);

        return is_array($policies) && array_key_exists($record::class, $policies);
    }
}
