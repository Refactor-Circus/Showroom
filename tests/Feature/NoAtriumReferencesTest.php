<?php

declare(strict_types=1);

/**
 * Every shipped or workbench file that must not name Atrium, relative to the
 * package root. The workbench's git-ignored storage and .env are local state,
 * not package files.
 *
 * @return list<string>
 */
function showroomAtriumFreeFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = ['composer.json'];

    foreach (['src', 'config', 'resources', 'workbench'] as $directory) {
        if (! is_dir($root.'/'.$directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $path = substr($file->getPathname(), strlen($root) + 1);

            if (str_starts_with($path, 'workbench/storage/') || in_array($path, ['workbench/.env', 'workbench/.env.dusk'], true)) {
                continue;
            }

            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

it('names Atrium in no file under src, config, resources, workbench or composer.json', function (): void {
    $root = dirname(__DIR__, 2);

    $naming = array_values(array_filter(
        showroomAtriumFreeFiles(),
        fn (string $path): bool => stripos((string) file_get_contents($root.'/'.$path), 'atrium') !== false,
    ));

    expect($naming)->toBe([]);
});

it('scans the package source, config and workbench', function (): void {
    expect(showroomAtriumFreeFiles())
        ->toContain('composer.json')
        ->toContain('config/showroom.php')
        ->toContain('src/ShowroomServiceProvider.php')
        ->toContain('workbench/app/Providers/WorkbenchServiceProvider.php');
});
