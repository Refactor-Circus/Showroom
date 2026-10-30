<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Atrium\Testing\ResetsScreenExtensions;
use RefactorCircus\Impex\ImpexServiceProvider;
use RefactorCircus\Showroom\ShowroomServiceProvider;

abstract class TestCase extends Orchestra
{
    // What one test registers on a screen never reaches the next.
    use ResetsScreenExtensions;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            ImpexServiceProvider::class,
            ShowroomServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // refactor-circus/cortex and refactor-circus/impex are dev dependencies, so Atrium
        // discovers their plugins here. Cortex's navigation would query missing tables, and
        // Showroom's screens must render without another package's dashboard UI: what Impex
        // adds to them comes through the screen registry, which these tests drive themselves.
        $app['config']->set('atrium.disabled', ['cortex', 'impex']);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        // Surface tests cover behaviour; PolicyTest turns authorization on.
        $app['config']->set('showroom.authorization', false);
        $app['config']->set('cache.default', 'array');
        // Index syncs and value purges run inline, so tests see their effect.
        $app['config']->set('queue.default', 'sync');
        // laravel/scout is a dev dependency, so the default would pick it.
        $app['config']->set('showroom.search.engine', 'database');
    }

    protected function defineDatabaseMigrations(): void
    {
        // Laravel's own migrations give the policy tests a real user to act as.
        $this->loadLaravelMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/impex/database/migrations');
    }
}
