<?php

declare(strict_types=1);

/*
 * Showroom ships its Atrium front-end module, JS only: sources in `atrium/`,
 * built with `pnpm build` into the committed `dist/atrium/`, and declared in
 * composer.json under `extra.atrium`. The build writes `build.json` with the
 * SHA-256 of the files it was built from; this recomputes it the way the
 * preset does (`hashInputs()` in @refactor-circus/atrium/vite), so a source
 * changed without a rebuild fails here with no Node needed. CI also rebuilds
 * and diffs (the `ui` job in .github/workflows/tests.yml). The module's
 * Atrium integration (nav, widgets, settings panel, search source, screens)
 * is declared in `atrium/src/integration.json` (D-090), which the build
 * checks and copies to `dist/atrium/integration.json`.
 */

function showroomPackageRoot(): string
{
    return dirname(__DIR__, 2);
}

/**
 * composer.json's `extra.atrium` block.
 *
 * @return array<string, mixed>
 */
function showroomAtriumDeclaration(): array
{
    /** @var array{extra?: array{atrium?: array<string, mixed>}} $composer */
    $composer = json_decode((string) file_get_contents(showroomPackageRoot().'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

    return $composer['extra']['atrium'] ?? [];
}

/**
 * The committed build's `build.json`.
 *
 * @return array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string}
 */
function showroomAtriumBuild(): array
{
    /** @var array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string} $info */
    $info = json_decode((string) file_get_contents(showroomPackageRoot().'/dist/atrium/build.json'), true, 512, JSON_THROW_ON_ERROR);

    return $info;
}

/**
 * The SHA-256 of `inputs` under `root`, as the preset's `hashInputs()` takes
 * it: every file (directories walked; node_modules, dist and dotfiles
 * skipped) as `{path}\0{sha256 of its content, CRLF as LF}\n`, sorted by path.
 *
 * @param  list<string>  $inputs
 */
function showroomAtriumSourcesHash(string $root, array $inputs): string
{
    $files = [];

    $walk = function (string $path) use (&$walk, &$files, $root): void {
        $absolute = $root.'/'.$path;

        if (! file_exists($absolute)) {
            return;
        }

        if (is_dir($absolute)) {
            foreach (scandir($absolute) ?: [] as $entry) {
                if (! in_array($entry, ['node_modules', 'dist', '.DS_Store', '.git'], true) && ! str_starts_with($entry, '.')) {
                    $walk($path.'/'.$entry);
                }
            }

            return;
        }

        $files[] = $path;
    };

    foreach ($inputs as $input) {
        $walk(rtrim($input, '/'));
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);

    $lines = array_map(
        fn (string $path): string => $path."\0".hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($root.'/'.$path)))."\n",
        $files,
    );

    return hash('sha256', implode('', $lines));
}

it('declares its Atrium module in composer.json extra.atrium', function (): void {
    expect(showroomAtriumDeclaration())->toBe([
        'key' => 'showroom',
        'scripts' => ['dist/atrium/atrium.js'],
        'styles' => ['dist/atrium/atrium.css'],
        'integration' => 'dist/atrium/integration.json',
    ]);
});

it('commits every script and stylesheet the declaration names', function (): void {
    /** @var list<string> $files */
    $files = [...(array) (showroomAtriumDeclaration()['scripts'] ?? []), ...(array) (showroomAtriumDeclaration()['styles'] ?? [])];

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        expect(showroomPackageRoot().'/'.$file)->toBeFile();
    }
});

it('ships its integration manifest where extra.atrium.integration points', function (): void {
    $path = showroomAtriumDeclaration()['integration'] ?? null;

    expect($path)->toBe('dist/atrium/integration.json')
        ->and(showroomPackageRoot().'/'.$path)->toBeFile();

    /** @var array<string, mixed> $manifest */
    $manifest = json_decode((string) file_get_contents(showroomPackageRoot().'/'.$path), true, 512, JSON_THROW_ON_ERROR);

    expect($manifest)
        ->toHaveKey('key', 'showroom')
        ->toHaveKey('version', 1);
});

it('ships the manifest its sources declare', function (): void {
    expect(json_decode((string) file_get_contents(showroomPackageRoot().'/dist/atrium/integration.json'), true, 512, JSON_THROW_ON_ERROR))
        ->toBe(json_decode((string) file_get_contents(showroomPackageRoot().'/atrium/src/integration.json'), true, 512, JSON_THROW_ON_ERROR));
});

it('was built for the showroom key and shared API 1.0', function (): void {
    expect(showroomAtriumBuild())
        ->key->toBe('showroom')
        ->shared_api->toBe('1.0');
});

it('was built from the module sources as they are now', function (): void {
    $info = showroomAtriumBuild();
    $root = realpath(showroomPackageRoot().'/dist/atrium/'.$info['root']);

    expect($root)->toBe(realpath(showroomPackageRoot().'/atrium'))
        ->and(showroomAtriumSourcesHash((string) $root, $info['inputs']))
        ->toBe($info['hash'], 'The Atrium module build is older than its sources: run `pnpm build` in atrium/ and commit dist/atrium.');
});

it('ships dist/atrium in the Composer archive and leaves the module sources out', function (): void {
    $attributes = (string) file_get_contents(showroomPackageRoot().'/.gitattributes');

    expect($attributes)
        ->toMatch('#^/atrium\s+export-ignore$#m')
        ->not->toMatch('#^/dist\S*\s+export-ignore$#m');
});
