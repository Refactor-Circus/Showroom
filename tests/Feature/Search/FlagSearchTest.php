<?php

declare(strict_types=1);

use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Search\Services\ScoutEngine;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\RecordingScoutEngine;

/*
 * One catalog, searched on both engines: a product matches `flags[]=` on its
 * effective flags — its own, its owner chain's, and for a variant its root
 * model's owner chain's — and must carry every flag asked for.
 */

/**
 * Search through the given engine from here on. Scout records what it indexes
 * in memory, so the catalog must be written after this.
 */
function searchFlagsOn(string $engine): ?RecordingScoutEngine
{
    if ($engine === 'database') {
        config()->set('showroom.search.engine', 'database');

        return null;
    }

    $scout = new RecordingScoutEngine;

    app()->singleton(EngineManager::class, fn ($app): EngineManager => new EngineManager($app));
    app(EngineManager::class)->extend('recording', fn (): RecordingScoutEngine => $scout);

    config()->set('scout.driver', 'recording');
    config()->set('showroom.search.engine', 'scout');

    return $scout;
}

/**
 * Owners acme (pricing_only) → acme-tools → acme-tools-pro (restricted), and
 * globex with nothing; products that carry flags of their own, inherit
 * them, or both.
 */
function seedFlaggedCatalog(): void
{
    Catalog::apparel();

    FlagModel::factory()->create(['code' => 'pricing_only', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 2]);
    FlagModel::factory()->create(['code' => 'clearance', 'applies_to' => ['product'], 'sort_order' => 3]);

    test()->postJson(route('showroom.owner-types.store'), ['code' => 'vendor', 'parent_types' => ['vendor']])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor', 'flags' => ['pricing_only']])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'acme-tools', 'type' => 'vendor', 'parent' => 'acme'])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'acme-tools-pro', 'type' => 'vendor', 'parent' => 'acme-tools', 'flags' => ['restricted']])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'globex', 'type' => 'vendor'])->assertCreated();

    foreach ([
        ['identifier' => 'OWN', 'owner' => 'globex', 'flags' => ['clearance']],
        ['identifier' => 'BOTH', 'owner' => 'globex', 'flags' => ['clearance', 'restricted']],
        ['identifier' => 'PLAIN', 'owner' => 'globex'],
        ['identifier' => 'INHERITED', 'owner' => 'acme-tools'],
        ['identifier' => 'GRANDCHILD', 'owner' => 'acme-tools-pro'],
    ] as $product) {
        test()->postJson(route('showroom.products.store'), $product)->assertCreated();
    }

    // A variant takes the owner of its root model, two levels up.
    test()->postJson(route('showroom.product-models.store'), ['code' => 'polo', 'family_variant' => 'shirts_by_color_size', 'owner' => 'acme-tools-pro'])->assertCreated();
    test()->postJson(route('showroom.product-models.store'), ['code' => 'polo-red', 'parent' => 'polo', 'values' => ['color' => Catalog::value('red')]])->assertCreated();
    test()->postJson(route('showroom.products.store'), ['identifier' => 'VARIANT', 'parent' => 'polo-red', 'values' => ['size' => Catalog::value('m')]])->assertCreated();
}

/**
 * The identifiers a product search by flags answers, sorted.
 *
 * @param  list<string>  $flags
 * @return list<string>
 */
function productsFlagged(array $flags): array
{
    /** @var list<string> $identifiers */
    $identifiers = collect(test()->getJson(route('showroom.products.index', ['flags' => $flags, 'per_page' => 100]))->assertOk()->json('data'))
        ->pluck('identifier')
        ->sort()
        ->values()
        ->all();

    return $identifiers;
}

/**
 * The id of an owner, which its routes bind by.
 */
function idOfFlaggedOwner(string $code): string
{
    return (string) OwnerModel::query()->where('code', $code)->value('id');
}

dataset('engines', ['database', 'scout']);

it('filters products on their effective flags, every flag asked for', function (string $engine, array $flags, array $expected): void {
    searchFlagsOn($engine);
    seedFlaggedCatalog();

    expect(productsFlagged($flags))->toBe($expected);
})->with('engines')->with([
    'own flags' => [['clearance'], ['BOTH', 'OWN']],
    'inherited from the owner chain, at any depth' => [['pricing_only'], ['GRANDCHILD', 'INHERITED', 'VARIANT']],
    'own or inherited' => [['restricted'], ['BOTH', 'GRANDCHILD', 'VARIANT']],
    'several flags, all required' => [['pricing_only', 'restricted'], ['GRANDCHILD', 'VARIANT']],
    'own and inherited together' => [['clearance', 'restricted'], ['BOTH']],
    'a flag nobody carries' => [['missing'], []],
]);

it('follows an owner\'s flag change and move once the sync has run', function (string $engine): void {
    searchFlagsOn($engine);
    seedFlaggedCatalog();

    // Syncs run inline in tests, after the update commits.
    test()->patchJson(route('showroom.owners.update', ['owner' => idOfFlaggedOwner('acme')]), ['flags' => ['pricing_only', 'restricted']])->assertOk();

    expect(productsFlagged(['restricted']))->toBe(['BOTH', 'GRANDCHILD', 'INHERITED', 'VARIANT']);

    test()->patchJson(route('showroom.owners.update', ['owner' => idOfFlaggedOwner('acme-tools')]), ['parent' => 'globex'])->assertOk();

    expect(productsFlagged(['pricing_only']))->toBe([])
        ->and(productsFlagged(['restricted']))->toBe(['BOTH', 'GRANDCHILD', 'VARIANT']);
})->with('engines');

it('filters owners on their effective flags', function (): void {
    seedFlaggedCatalog();

    $owners = fn (array $flags): array => collect(test()->getJson(route('showroom.owners.index', ['flags' => $flags]))->assertOk()->json('data'))->pluck('code')->all();

    expect($owners(['pricing_only']))->toBe(['acme', 'acme-tools', 'acme-tools-pro'])
        ->and($owners(['restricted']))->toBe(['acme-tools-pro'])
        ->and($owners(['pricing_only', 'restricted']))->toBe(['acme-tools-pro'])
        ->and($owners(['clearance']))->toBe([]);
});

it('indexes effective flags normalized, and filters on the normalized code, quoted', function (): void {
    $scout = searchFlagsOn('scout');
    seedFlaggedCatalog();

    expect(collect($scout?->documents)->pluck('flags', 'identifier')->sortKeys()->all())->toBe([
        'BOTH' => ['restricted', 'clearance'],
        'GRANDCHILD' => ['pricing_only', 'restricted'],
        'INHERITED' => ['pricing_only'],
        'OWN' => ['clearance'],
        'PLAIN' => [],
        'VARIANT' => ['pricing_only', 'restricted'],
    ]);

    expect(productsFlagged(['PRICING_ONLY', 'Restricted']))->toBe(['GRANDCHILD', 'VARIANT']);

    $search = end($scout->searches);

    expect($search->wheres)->toBe([
        ['field' => 'flags', 'operator' => '=', 'value' => 'pricing_only'],
        ['field' => 'flags', 'operator' => '=', 'value' => 'restricted'],
    ]);

    // How Scout's Meilisearch engine writes those wheres into its filter.
    $meilisearch = (new ReflectionClass(MeilisearchEngine::class))->newInstanceWithoutConstructor();

    expect((fn (Builder $builder): string => $this->filters($builder))->call($meilisearch, $search))
        ->toBe('flags="pricing_only" AND flags="restricted"');
});

it('lists slug, external ids, flags and owner slugs in the Scout index settings', function (): void {
    expect(ScoutEngine::indexSettings()['filterableAttributes'])
        ->toContain('slug', 'external_ids', 'flags', 'owner_slugs', 'owners', 'family', 'changed_at');
});
