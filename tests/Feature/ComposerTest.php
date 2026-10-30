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

it('requires Keystone as its only suite package', function (): void {
    $suite = array_values(array_filter(
        array_keys(showroomComposer()['require']),
        fn (string $package): bool => str_starts_with($package, 'refactor-circus/'),
    ));

    expect($suite)->toBe(['refactor-circus/keystone']);
});

it('keeps Atrium optional: a dev dependency it suggests', function (): void {
    $composer = showroomComposer();

    expect($composer['require'])->not->toHaveKey('refactor-circus/atrium')
        ->and($composer['require-dev'])->toHaveKey('refactor-circus/atrium', 'dev-main')
        ->and($composer['suggest'])->toHaveKey('refactor-circus/atrium', 'Browse and manage the catalog from the Atrium dashboard (dev-main).');
});

it('refuses an Atrium older than the screen extensions registry', function (): void {
    expect(showroomComposer()['conflict'] ?? [])->toHaveKey('refactor-circus/atrium')
        ->and(showroomComposer()['conflict']['refactor-circus/atrium'])->toStartWith('<');
});

it('runs the without-Atrium suite in composer test, after the unit suite', function (): void {
    $scripts = showroomComposer()['scripts'];

    expect($scripts)->toHaveKey('test:without-atrium')
        ->and(implode(' ', (array) $scripts['test:without-atrium']))->toContain('phpunit.without-atrium.xml');

    $test = array_values((array) $scripts['test']);

    expect($test)->toContain('@test:unit', '@test:without-atrium')
        ->and(array_search('@test:without-atrium', $test, true))->toBeGreaterThan(array_search('@test:unit', $test, true));
});
