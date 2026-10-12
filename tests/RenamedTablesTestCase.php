<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests;

use RefactorCircus\Showroom\Support\Tables;

/**
 * Every Showroom table renamed through `showroom.tables` before migrating.
 */
abstract class RenamedTablesTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        foreach (array_keys(Tables::DEFAULTS) as $key) {
            $app['config']->set('showroom.tables.'.$key, 'crm_'.$key);
        }
    }
}
