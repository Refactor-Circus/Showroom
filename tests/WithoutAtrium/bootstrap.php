<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

/*
 * Boot the suite as if refactor-circus/atrium were not installed: Composer
 * no longer finds any `RefactorCircus\Atrium\` class, so an attempt to load
 * one fails as it would in an application without Atrium.
 */

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__, 2).'/vendor/autoload.php';

$atrium = 'RefactorCircus\\Atrium\\';

$loader->setPsr4($atrium, []);

// An optimized autoloader lists classes in its class map too; a false entry
// stops the lookup there, as an unknown class would.
$loader->addClassMap(array_fill_keys(
    array_values(array_filter(
        array_keys($loader->getClassMap()),
        fn (string $class): bool => str_starts_with($class, $atrium),
    )),
    false,
));
