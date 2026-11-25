<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Catalog\Actions;

use RefactorCircus\Showroom\Domains\Catalog\Events\SettingsSummaryShowingActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Events\SettingsSummaryShownActionEvent;

/**
 * @phpstan-type Summary array{
 *     authorization: bool,
 *     routes: array{enabled: bool, prefix: string, middleware: list<string>},
 *     mcp: array{web: array{enabled: bool, route: string, middleware: list<string>}, local: array{enabled: bool, handle: string}},
 *     pagination: array{per_page: int, max_per_page: int},
 * }
 */
final class ShowSettingsSummaryAction
{
    /**
     * How Showroom is set up for its API and MCP, each value cast as the
     * package reads it: a switch is on only when it is `true`, middleware
     * is a list of names, and a page size that is no number is the default.
     *
     * @return Summary
     */
    public function execute(): array
    {
        SettingsSummaryShowingActionEvent::dispatch();

        $summary = $this->perform();

        SettingsSummaryShownActionEvent::dispatch($summary);

        return $summary;
    }

    /**
     * @return Summary
     */
    private function perform(): array
    {
        return [
            'authorization' => config('showroom.authorization') === true,
            'routes' => [
                'enabled' => config('showroom.routes.enabled') === true,
                'prefix' => self::string('showroom.routes.prefix'),
                'middleware' => self::names('showroom.routes.middleware'),
            ],
            'mcp' => [
                'web' => [
                    'enabled' => config('showroom.mcp.web.enabled') === true,
                    'route' => self::string('showroom.mcp.web.route'),
                    'middleware' => self::names('showroom.mcp.web.middleware'),
                ],
                'local' => [
                    'enabled' => config('showroom.mcp.local.enabled') === true,
                    'handle' => self::string('showroom.mcp.local.handle'),
                ],
            ],
            'pagination' => [
                'per_page' => self::integer('showroom.pagination.per_page', 25),
                'max_per_page' => self::integer('showroom.pagination.max_per_page', 100),
            ],
        ];
    }

    private static function string(string $key): string
    {
        $value = config($key);

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * A middleware list: one name or several, anything but a name left out.
     *
     * @return list<string>
     */
    private static function names(string $key): array
    {
        return array_values(array_filter((array) config($key, []), is_string(...)));
    }

    private static function integer(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }
}
