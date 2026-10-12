<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests;

use RefactorCircus\Keen\KeenServiceProvider;

/**
 * Showroom booted with refactor-circus/keen recording the suite-wide audit log.
 */
abstract class KeenTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            KeenServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(dirname(__DIR__).'/vendor/refactor-circus/keen/database/migrations');
    }
}
