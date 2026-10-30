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

// Pest reads tests/Pest.php before this bootstrap runs, and its uses() loads the
// main suite's TestCase, which uses Atrium's ResetsScreenExtensions. Those
// classes were loaded by the test harness, not by Showroom; the suite records
// them so it can tell them apart from anything Showroom loads afterwards.
$GLOBALS['showroom_atrium_preloaded'] = array_values(array_filter(
    [...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()],
    fn (string $class): bool => str_starts_with($class, $atrium),
));

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
