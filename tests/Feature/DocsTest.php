<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

function showroomRoot(): string
{
    return dirname(__DIR__, 2);
}

function showroomReadme(): string
{
    return (string) file_get_contents(showroomRoot().'/README.md');
}

dataset('declared screens', [
    'showroom.products' => ['showroom.products', 'list'],
    'showroom.product-create' => ['showroom.product-create', 'detail'],
    'showroom.product' => ['showroom.product', 'detail'],
    'showroom.product-models' => ['showroom.product-models', 'list'],
    'showroom.product-model' => ['showroom.product-model', 'detail'],
    'showroom.channels' => ['showroom.channels', 'list'],
    'showroom.channel' => ['showroom.channel', 'detail'],
    'showroom.assets' => ['showroom.assets', 'list'],
    'showroom.asset' => ['showroom.asset', 'detail'],
    'showroom.categories' => ['showroom.categories', 'list'],
    'showroom.category' => ['showroom.category', 'detail'],
    'showroom.owner-types' => ['showroom.owner-types', 'list'],
    'showroom.owner-type' => ['showroom.owner-type', 'detail'],
    'showroom.owners' => ['showroom.owners', 'list'],
    'showroom.owner' => ['showroom.owner', 'detail'],
    'showroom.families' => ['showroom.families', 'list'],
    'showroom.family' => ['showroom.family', 'detail'],
    'showroom.family-variant' => ['showroom.family-variant', 'detail'],
    'showroom.attributes' => ['showroom.attributes', 'list'],
    'showroom.attribute-create' => ['showroom.attribute-create', 'detail'],
    'showroom.attribute' => ['showroom.attribute', 'detail'],
    'showroom.attribute-groups' => ['showroom.attribute-groups', 'list'],
    'showroom.attribute-group' => ['showroom.attribute-group', 'detail'],
    'showroom.association-types' => ['showroom.association-types', 'list'],
    'showroom.transfers' => ['showroom.transfers', 'list'],
]);

it('lists every declared screen with its kind in the README', function (string $key, string $kind): void {
    expect(showroomReadme())->toMatch('/^\| `'.preg_quote($key, '/').'` \| '.$kind.' \|/m');
})->with('declared screens');

it('lists exactly the 25 declared screens in the README', function (): void {
    preg_match_all('/^\| `(showroom\.[a-z-]+)` \| (?:list|detail) \|/m', showroomReadme(), $rows);

    expect($rows[1])->toHaveCount(25)
        ->and(array_unique($rows[1]))->toHaveCount(25);
});

it('names the slots each kind of screen declares', function (): void {
    expect(showroomReadme())
        ->toContain('List screens declare `header.actions`, `before`, `after` and `row.actions`')
        ->toContain('detail screens declare `header.actions`, `before`, `after` and `aside`');
});

it('says permission() extensions follow showroom.authorization, and authorize() is for their own check', function (): void {
    expect(showroomReadme())
        ->toContain('`permission()` extensions follow `showroom.authorization`')
        ->toContain('with it off, every one shows')
        ->toContain('registrants that need their own check use `authorize()`');
});

it('says row closures run once per row and must not query', function (): void {
    expect(showroomReadme())
        ->toContain('run once per row')
        ->toContain('must not query');
});

it('says Atrium is optional', function (): void {
    expect(showroomReadme())
        ->toContain('Atrium is optional')
        ->toContain('composer require refactor-circus/atrium');
});

it('tells upgraders to install Atrium, re-publish views and install Keen for History', function (): void {
    $changelog = (string) file_get_contents(showroomRoot().'/CHANGELOG.md');

    expect($changelog)
        ->toContain('composer require refactor-circus/atrium')
        ->toContain('--tag="showroom-views"')
        ->toContain('refactor-circus/keen');
});

it('mentions no removed audit trail, History nav item or Impex run link', function (): void {
    $root = showroomRoot();

    $files = iterator_to_array(Finder::create()->files()->in([
        $root.'/src',
        $root.'/resources',
        $root.'/docs/agent/standards',
    ]), false);

    $paths = [
        $root.'/README.md',
        $root.'/CLAUDE.md',
        ...array_map(fn (SplFileInfo $file): string => $file->getPathname(), $files),
    ];

    $offending = [];

    foreach ($paths as $path) {
        $contents = (string) file_get_contents($path);

        foreach (['audit-trail', 'historyNavItem', 'atrium.impex'] as $needle) {
            if (str_contains($contents, $needle)) {
                $offending[] = str_replace($root.'/', '', $path).': '.$needle;
            }
        }
    }

    expect($offending)->toBe([]);
});
