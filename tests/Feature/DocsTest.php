<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

function showroomRoot(): string
{
    return dirname(__DIR__, 2);
}

function showroomReadme(): string
{
    return (string) file_get_contents(showroomRoot().'/README.md');
}

function showroomDoc(string $page): string
{
    $path = showroomRoot().'/docs/'.$page;

    expect($path)->toBeFile();

    return (string) file_get_contents($path);
}

function showroomChangelog(): string
{
    return (string) file_get_contents(showroomRoot().'/CHANGELOG.md');
}

/**
 * The keys the configuration page's table documents, with a trailing `.*`
 * dropped: `tables.*` documents every key under `tables`.
 *
 * @return list<string>
 */
function showroomDocumentedConfigKeys(): array
{
    preg_match_all('/^\| `([a-z_.*]+)` \|/m', showroomDoc('13-configuration.md'), $rows);

    return array_map(fn (string $key): string => preg_replace('/\.\*$/', '', $key) ?? $key, $rows[1]);
}

/**
 * Every key the shipped config file sets, in dot notation, down to its
 * leaves (an empty array is a leaf).
 *
 * @return list<string>
 */
function showroomShippedConfigKeys(): array
{
    /** @var array<string, mixed> $config */
    $config = require showroomRoot().'/config/showroom.php';

    return array_map('strval', array_keys(Arr::dot($config)));
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
    'showroom.flags' => ['showroom.flags', 'list'],
    'showroom.flag' => ['showroom.flag', 'detail'],
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

it('lists exactly the 27 declared screens in the README', function (): void {
    preg_match_all('/^\| `(showroom\.[a-z-]+)` \| (?:list|detail) \|/m', showroomReadme(), $rows);

    expect($rows[1])->toHaveCount(27)
        ->and(array_unique($rows[1]))->toHaveCount(27);
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

it('documents every key the shipped config sets on the configuration page', function (): void {
    $documented = showroomDocumentedConfigKeys();

    $undocumented = array_values(array_filter(
        showroomShippedConfigKeys(),
        fn (string $key): bool => ! array_any($documented, fn (string $row): bool => $key === $row || str_starts_with($key, $row.'.')),
    ));

    expect($undocumented)->toBe([]);
});

it('documents no config key the shipped config does not set', function (): void {
    $shipped = showroomShippedConfigKeys();

    $unknown = array_values(array_filter(
        showroomDocumentedConfigKeys(),
        fn (string $row): bool => ! array_any($shipped, fn (string $key): bool => $key === $row || str_starts_with($key, $row.'.')),
    ));

    expect($unknown)->toBe([]);
});

it('documents the identifier pattern, the lookup keys and the new tables with their defaults', function (): void {
    $page = showroomDoc('13-configuration.md');

    expect($page)
        ->toMatch('/^\| `products\.identifier_pattern` \| .+ \| .*`identifier`.* \|$/m')
        ->toMatch('/^\| `lookup\.products` \| `id` \| .*`slug`.* \|$/m')
        ->toMatch('/^\| `lookup\.owners` \| `id` \| .*`slug`.* \|$/m')
        ->toMatch('/^\| `tables\.\*` \| .*`external_ids`.*`flags`.*`flag_assignments`.* \|$/m')
        ->toMatch('/^\| `policies` \| .*`assign`.* \|$/m');
});

it('has a flags page covering definitions, inheritance, assign, search and every surface', function (): void {
    expect(showroomDoc('14-flags.md'))
        ->toContain('# Flags')
        ->toContain('`applies_to`')
        ->toContain('`effective_flags`')
        ->toContain('`assign`')
        ->toContain('FlagPolicy')
        ->toContain('`flags[]=')
        ->toContain('/showroom/flags/{flag}')
        ->toContain('list-flags-tool')
        ->toContain('show-flag-tool')
        ->toContain('create-flag-tool')
        ->toContain('update-flag-tool')
        ->toContain('delete-flag-tool')
        ->toContain('ListFlagsAction')
        ->toContain('DeleteFlagAction');
});

it('links the flags page from the products, ownership, search, API and MCP pages', function (string $page): void {
    expect(showroomDoc($page))->toContain('(14-flags.md');
})->with(['04-products.md', '06-ownership.md', '05-search.md', '09-api.md', '10-mcp.md']);

it('links only to doc pages that exist', function (): void {
    $broken = [];

    foreach (glob(showroomRoot().'/docs/*.md') ?: [] as $path) {
        preg_match_all('/\]\(([0-9a-z-]+\.md)(?:#[^)]*)?\)/', (string) file_get_contents($path), $links);

        foreach ($links[1] as $link) {
            if (! is_file(showroomRoot().'/docs/'.$link)) {
                $broken[] = basename($path).' → '.$link;
            }
        }
    }

    expect($broken)->toBe([]);
});

it('documents SKU identifiers, slugs, lookup by id or slug, and external ids', function (): void {
    expect(showroomDoc('04-products.md'))
        ->toContain('showroom.products.identifier_pattern')
        ->toContain('`slug`')
        ->toContain('`external_ids`')
        ->toContain('`?by=');

    expect(showroomDoc('06-ownership.md'))
        ->toContain('`slug`')
        ->toContain('`external_ids`')
        ->toContain('`parent_types: ["vendor"]`');

    expect(showroomDoc('09-api.md'))
        ->toContain('`showroom.lookup.products`')
        ->toContain('`?by=slug`')
        ->not->toContain('Path parameters are codes — identifiers for products.');

    expect(showroomDoc('10-mcp.md'))
        ->toContain('`by`')
        ->not->toContain('`product` takes an identifier');

    expect(showroomDoc('05-search.md'))
        ->toContain('`external[{source}]=')
        ->toContain('ScoutEngine::indexSettings()');
});

it('documents external ids by source, with their sync stamp and one id per record and source', function (): void {
    expect(showroomDoc('04-products.md'))
        ->toContain('`{source: id}`')
        ->toContain('`synced_at`')
        ->toContain('`external_ids_synced_at`')
        ->toContain('`(subject_type, subject_id, source)`');

    expect(showroomDoc('10-mcp.md'))->toContain('`external_ids_synced_at`');

    $unreleased = str(showroomChangelog())->after('## [Unreleased]')->before('## [v0.1.0]')->toString();

    expect($unreleased)
        ->toContain('`{source: id}`')
        ->toContain('`synced_at`')
        ->toContain('`external_ids_synced_at`');

    $pages = ['04-products.md', '05-search.md', '06-ownership.md', '09-api.md', '10-mcp.md', '12-impex.md'];

    foreach ([...array_map(showroomDoc(...), $pages), $unreleased] as $text) {
        expect($text)
            ->not->toContain('{system: id}')
            ->not->toContain('external[{system}]')
            ->not->toContain('{system}:{id}');
    }
});

it('documents the batch upsert and the webhook opt-out', function (): void {
    expect(showroomDoc('12-impex.md'))
        ->toContain('UpsertProductsAction')
        ->toContain('`unchanged`')
        ->toContain('--capture-changes=0')
        ->toContain('capture_changes')
        ->not->toContain('Rows go through `CreateProductAction` and `UpdateProductAction`');
});

it('gives upgraders notes for lookup keys, read-only keys, the import step, the slug index and Scout', function (): void {
    $unreleased = str(showroomChangelog())->after('## [Unreleased]')->before('## [v0.1.0]')->toString();

    expect($unreleased)
        ->toContain('`showroom.lookup.products`')
        ->toContain('`?by=')
        ->toContain('`slug`')
        ->toContain('`effective_flags`')
        ->toContain('`ImportProductRow`')
        ->toContain('`ProductRowImportedActionEvent`')
        ->toContain('`UpsertProductChunk`')
        ->toContain('lock')
        ->toContain('ScoutEngine::indexSettings()');
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
