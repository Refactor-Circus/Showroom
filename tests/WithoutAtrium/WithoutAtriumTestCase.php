<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests\WithoutAtrium;

use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Showroom\ShowroomServiceProvider;

/**
 * Showroom booted the way an application without refactor-circus/atrium
 * boots it: no Atrium provider, and (through tests/WithoutAtrium/bootstrap.php)
 * no Atrium class to load. Impex, Cortex and Keen require Atrium, so none of
 * their providers is registered either.
 */
abstract class WithoutAtriumTestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            ShowroomServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('showroom.authorization', false);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('queue.default', 'sync');
        // laravel/scout is a dev dependency, so the default would pick it.
        $app['config']->set('showroom.search.engine', 'database');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');
    }
}
