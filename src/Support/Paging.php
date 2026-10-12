<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Support;

use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * How Showroom's lists page, sized by `showroom.pagination`: every list but
 * product search follows a cursor; product search counts numbered pages.
 */
final class Paging
{
    public static function cursor(): Pagination
    {
        return Pagination::cursor(self::default(), self::max());
    }

    public static function pages(): Pagination
    {
        return Pagination::pages(self::default(), self::max());
    }

    private static function default(): int
    {
        $default = config('showroom.pagination.per_page', 25);

        return is_numeric($default) ? (int) $default : 25;
    }

    private static function max(): int
    {
        $max = config('showroom.pagination.max_per_page', 100);

        return is_numeric($max) ? (int) $max : 100;
    }
}
