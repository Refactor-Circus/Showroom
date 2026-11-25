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
 * The CHANGELOG's next release: everything under its v2.0.0 heading.
 */
function showroomNextRelease(): string
{
    return str(showroomChangelog())->after('## [v2.0.0]')->before('## [v0.1.0]')->toString();
}

/**
 * One `##` section of the README, from its heading to the next `##` heading.
 */
function showroomReadmeSection(string $heading): string
{
    expect(showroomReadme())->toContain("\n## {$heading}\n");

    return str(showroomReadme())->after("\n## {$heading}\n")->before("\n## ")->toString();
}

/**
 * The guides under docs/, by file name (the agent folder excluded).
 *
 * @return array<string, string>
 */
function showroomDocPages(): array
{
    $pages = [];

    foreach (glob(showroomRoot().'/docs/*.md') ?: [] as $path) {
        $pages[basename($path)] = (string) file_get_contents($path);
    }

    return $pages;
}

it('describes Showroom as a JSON API and MCP package with no dashboard or screen extensions', function (): void {
    expect(showroomReadme())
        ->toContain('JSON API')
        ->toContain('MCP server')
        ->not->toMatch('/^#+ Dashboard$/m')
        ->not->toMatch('/^#+ Screen extensions$/m')
        ->not->toContain('Atrium is optional')
        ->not->toContain('showroom-views')
        ->not->toContain('showroomCan')
        ->not->toContain('ShowroomSupportFeature')
        ->not->toContain('x-atrium')
        ->not->toContain('11-dashboard.md');
});

it('points front ends at Atrium v2, which lists runs through Impex and starts them through Showroom', function (): void {
    expect(showroomReadmeSection('Front end: Atrium v2'))
        ->toContain('optional')
        ->toContain('refactor-circus/atrium')
        ->toContain('runs API')
        ->toContain('`flow`')
        ->toContain('`POST /showroom/imports`')
        ->toContain('`POST /showroom/exports`')
        ->toContain('no transfer record');
});

it('explains the OpenAPI document and the TypeScript client, their regeneration and a git dependency', function (): void {
    expect(showroomReadmeSection('OpenAPI and TypeScript client'))
        ->toContain('`openapi.json`')
        ->toContain('`client/`')
        ->toContain('@refactor-circus/showroom-client')
        ->toContain('composer client:generate')
        ->toContain('tests/OpenApi/OpenApiTest.php')
        ->toContain('#v2.0.0&path:client')
        ->toContain('createShowroomClient');
});

it('lists the stats, search and settings summary endpoints under HTTP API', function (): void {
    expect(showroomReadmeSection('HTTP API'))
        ->toContain('`/showroom/stats`')
        ->toContain('`/showroom/search`')
        ->toContain('`/showroom/settings-summary`');
});

it('has no dashboard page, and documents the new endpoints and tools on the API and MCP pages', function (): void {
    expect(showroomRoot().'/docs/11-dashboard.md')->not->toBeFile()
        ->and(showroomDoc('README.md'))->not->toContain('11-dashboard.md');

    expect(showroomDoc('09-api.md'))
        ->toContain('`/showroom/stats`')
        ->toContain('`/showroom/search`')
        ->toContain('`/showroom/settings-summary`')
        ->toContain('ShowCatalogStatsAction')
        ->toContain('SearchCatalogAction')
        ->toContain('ShowSettingsSummaryAction')
        ->toContain('`showroom.stats.cache_seconds`')
        ->toContain('`review_queue`')
        ->toContain('`recent_versions`')
        ->toContain('`q`')
        ->toContain('`limit`');

    expect(showroomDoc('10-mcp.md'))
        ->toContain('show-catalog-stats-tool')
        ->toContain('search-catalog-tool');
});

it('leaves no Atrium screen reference in the guides', function (): void {
    $offending = [];

    foreach (showroomDocPages() as $page => $text) {
        foreach (['atrium', 'the dashboard', 'showroom-views', 'showroomcan', 'showroomsupportfeature', 'viewatrium', 'showroom.ui.', 'pennant'] as $needle) {
            if (str_contains(strtolower($text), $needle)) {
                $offending[] = "{$page}: {$needle}";
            }
        }
    }

    expect($offending)->toBe([]);
});

it('heads the next release v2.0.0 with an upgrade note naming every removal and the stats key', function (): void {
    expect(showroomChangelog())
        ->not->toContain('## [Unreleased]')
        ->toMatch('/^## \[v2\.0\.0\]/m');

    $upgrade = str(showroomNextRelease())->after('### Upgrading to v2.0.0')->before("\n### ")->toString();

    expect($upgrade)
        ->toContain('Atrium UI')
        ->toContain('`showroom.ui.*`')
        ->toContain('`showroom.atrium.*`')
        ->toContain('`ShowroomSupportFeature`')
        ->toContain('`showroom-views`')
        ->toContain('`ProductStatus::badge()`')
        ->toContain('`showroom.atrium.widget_cache_seconds`')
        ->toContain('`showroom.stats.cache_seconds`')
        ->toContain('Atrium v2')
        ->toContain('refactor-circus/pennantplus');
});

it('lists the new endpoints, MCP tools, openapi.json and the client as added in v2.0.0', function (): void {
    $added = str(showroomNextRelease())->after('### Added')->before("\n### ")->toString();

    expect($added)
        ->toContain('`GET /showroom/stats`')
        ->toContain('`GET /showroom/search`')
        ->toContain('`GET /showroom/settings-summary`')
        ->toContain('`show-catalog-stats-tool`')
        ->toContain('`search-catalog-tool`')
        ->toContain('`openapi.json`')
        ->toContain('`client/`');
});

it('describes the package as API and MCP in the Boost skill and CLAUDE.md, without Atrium screen conventions', function (string $file): void {
    $text = (string) file_get_contents(showroomRoot().'/'.$file);

    expect($text)
        ->toContain('JSON API and MCP')
        ->toContain('Domains\Catalog')
        ->toContain('composer client:generate')
        ->toContain('OpenApiTest');

    foreach (['AuthorizesScreens', 'ScreenAccess', 'x-atrium', 'AtriumStyles', 'registerAtriumPlugin', 'WithoutAtrium', 'without-atrium', 'showroomCan', 'ShowroomSupportFeature', 'src/Atrium', 'screen extensions', 'loadViewsFrom'] as $needle) {
        expect(stripos($text, $needle))->toBeFalse("{$file} still names {$needle}");
    }
})->with(['CLAUDE.md', 'resources/boost/skills/showroom-development/SKILL.md']);

it('allows CLAUDE.md config keys to be removed only in a major release with an upgrade note', function (): void {
    expect((string) file_get_contents(showroomRoot().'/CLAUDE.md'))
        ->not->toContain('Existing keys are never renamed or removed;')
        ->toContain('only in a major release')
        ->toContain('upgrade note');
});

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

    $unreleased = showroomNextRelease();

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
    $unreleased = showroomNextRelease();

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
