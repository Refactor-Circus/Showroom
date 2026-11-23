<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * The application `composer openapi:export` exports from: testbench.yaml's
 * providers and discovered packages, Scramble, and the workbench provider,
 * which loads workbench/config/scramble.php and configures the document. The
 * database stays empty, as the export's does, so Scramble reads the same
 * models.
 */
abstract class OpenApiTestCase extends Orchestra
{
    use WithWorkbench;

    /**
     * Boot the discovered packages (Impex, Keen, Cortex, MCP) as the
     * Testbench CLI does: Showroom documents its transfer routes only while
     * Impex is loaded.
     *
     * @var bool
     */
    protected $enablesPackageDiscoveries = true;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ScrambleServiceProvider::class,
        ];
    }
}
