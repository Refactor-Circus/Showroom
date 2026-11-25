<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function showroomComposer(): array
{
    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

    return $composer;
}

/**
 * The packages listed under one composer.json section.
 *
 * @return list<string>
 */
function showroomComposerPackages(string $section): array
{
    /** @var array<string, string> $packages */
    $packages = showroomComposer()[$section] ?? [];

    return array_map('strval', array_keys($packages));
}

it('requires Keystone as its only suite package', function (): void {
    $suite = array_values(array_filter(
        showroomComposerPackages('require'),
        fn (string $package): bool => str_starts_with($package, 'refactor-circus/'),
    ));

    expect($suite)->toBe(['refactor-circus/keystone']);
});

it('requires only PHP, Laravel packages and Keystone', function (): void {
    $other = array_values(array_filter(
        showroomComposerPackages('require'),
        fn (string $package): bool => $package !== 'php'
            && $package !== 'refactor-circus/keystone'
            && ! str_starts_with($package, 'laravel/')
            && ! str_starts_with($package, 'illuminate/'),
    ));

    expect($other)->toBe([]);
});

it('has no Atrium dependency, suggestion or conflict', function (string $section): void {
    expect(showroomComposerPackages($section))->not->toContain('refactor-circus/atrium');
})->with(['require', 'require-dev', 'suggest', 'conflict']);

it('declares only its Atrium front-end module under extra.atrium, no PHP plugin', function (): void {
    /** @var array<string, mixed> $extra */
    $extra = showroomComposer()['extra'] ?? [];

    expect($extra['atrium'] ?? null)->toBe([
        'key' => 'showroom',
        'scripts' => ['dist/atrium/atrium.js'],
        'styles' => ['dist/atrium/atrium.css'],
        'integration' => 'dist/atrium/integration.json',
    ]);
});

it('declares no Atrium repository', function (): void {
    /** @var list<array{type?: string, url?: string}> $repositories */
    $repositories = showroomComposer()['repositories'] ?? [];

    $urls = array_map(fn (array $repository): string => strtolower($repository['url'] ?? ''), $repositories);

    expect(array_filter($urls, fn (string $url): bool => str_contains($url, 'atrium')))->toBe([]);
});

it('drops PennantPlus: no dependency, suggestion or repository', function (string $section): void {
    expect(showroomComposerPackages($section))->not->toContain('refactor-circus/pennantplus');
})->with(['require', 'require-dev', 'suggest']);

it('declares no PennantPlus repository', function (): void {
    /** @var list<array{type?: string, url?: string}> $repositories */
    $repositories = showroomComposer()['repositories'] ?? [];

    $urls = array_map(fn (array $repository): string => strtolower($repository['url'] ?? ''), $repositories);

    expect(array_filter($urls, fn (string $url): bool => str_contains($url, 'pennantplus')))->toBe([]);
});

it('has no without-Atrium script, and composer test does not call one', function (): void {
    /** @var array<string, string|list<string>> $scripts */
    $scripts = showroomComposer()['scripts'];

    expect($scripts)->not->toHaveKey('test:without-atrium')
        ->and(array_values((array) $scripts['test']))->toBe(['@analyse', '@lint:check', '@test:types', '@test:unit']);
});

it('suggests Keen without mentioning Atrium', function (): void {
    /** @var array<string, string> $suggest */
    $suggest = showroomComposer()['suggest'];

    expect($suggest)->toHaveKey('refactor-circus/keen')
        ->and(strtolower($suggest['refactor-circus/keen']))->not->toContain('atrium');
});
