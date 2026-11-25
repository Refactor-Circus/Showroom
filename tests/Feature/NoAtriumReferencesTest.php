<?php

declare(strict_types=1);

/*
 * Showroom's PHP knows nothing of Atrium: no class, config key, route or
 * workbench file names it. The package does ship its Atrium front-end module
 * (JS only): its sources in `atrium/`, its build in `dist/atrium/`, the
 * `extra.atrium` block of composer.json that declares it, the CI job that
 * builds it and the docs that describe it. Those are the only places Atrium
 * may be named.
 */

/**
 * Every PHP-side file that must not name Atrium, relative to the package
 * root. The workbench's git-ignored storage and .env are local state, not
 * package files; the Boost skill is documentation and may describe the module.
 *
 * @return list<string>
 */
function showroomAtriumFreeFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    foreach (['src', 'config', 'routes', 'database', 'lang', 'resources', 'workbench'] as $directory) {
        if (! is_dir($root.'/'.$directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = substr($file->getPathname(), strlen($root) + 1);

            if (str_starts_with($path, 'workbench/storage/')
                || str_starts_with($path, 'resources/boost/')
                || in_array($path, ['workbench/.env', 'workbench/.env.dusk'], true)) {
                continue;
            }

            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

/**
 * composer.json without its `extra.atrium` module declaration.
 */
function showroomComposerWithoutModule(): string
{
    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

    /** @var array<string, mixed> $extra */
    $extra = $composer['extra'] ?? [];
    unset($extra['atrium']);
    $composer['extra'] = $extra;

    return (string) json_encode($composer, JSON_THROW_ON_ERROR);
}

it('names Atrium in no file under src, config, routes, database, lang, resources or workbench', function (): void {
    $root = dirname(__DIR__, 2);

    $naming = array_values(array_filter(
        showroomAtriumFreeFiles(),
        fn (string $path): bool => stripos((string) file_get_contents($root.'/'.$path), 'atrium') !== false,
    ));

    expect($naming)->toBe([]);
});

it('names Atrium in composer.json only in the extra.atrium module declaration', function (): void {
    expect(stripos(showroomComposerWithoutModule(), 'atrium'))->toBeFalse();
});

it('scans the package source, config and workbench', function (): void {
    expect(showroomAtriumFreeFiles())
        ->toContain('config/showroom.php')
        ->toContain('src/ShowroomServiceProvider.php')
        ->toContain('workbench/app/Providers/WorkbenchServiceProvider.php');
});
