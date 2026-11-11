<?php

declare(strict_types=1);

use Laravel\Scout\EngineManager;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductDocument;
use RefactorCircus\Showroom\Domains\Search\Services\ScoutEngine;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\RecordingScoutEngine;

/*
 * One catalog, searched on both engines: `effective_enabled` holds a product
 * that is enabled, and whose owner chain — its effective owner (a variant's
 * root model's owner) and every owner above it — is enabled too.
 */

/**
 * Search through the given engine from here on. Scout records what it indexes
 * in memory, so the catalog must be written after this.
 */
function searchEffectiveEnabledOn(string $engine): ?RecordingScoutEngine
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
 * Owners acme → acme-tools → acme-tools-pro, and globex; products beneath
 * each, one disabled itself, one without an owner, and a variant whose root
 * model belongs to acme-tools-pro.
 */
function seedEnabledCatalog(): void
{
    Catalog::apparel();

    test()->postJson(route('showroom.owner-types.store'), ['code' => 'vendor', 'parent_types' => ['vendor']])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor'])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'acme-tools', 'type' => 'vendor', 'parent' => 'acme'])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'acme-tools-pro', 'type' => 'vendor', 'parent' => 'acme-tools'])->assertCreated();
    test()->postJson(route('showroom.owners.store'), ['code' => 'globex', 'type' => 'vendor'])->assertCreated();

    foreach ([
        ['identifier' => 'ON', 'owner' => 'globex'],
        ['identifier' => 'OFF', 'owner' => 'globex', 'enabled' => false],
        ['identifier' => 'NO-OWNER'],
        ['identifier' => 'CHILD', 'owner' => 'acme-tools'],
        ['identifier' => 'GRANDCHILD', 'owner' => 'acme-tools-pro'],
    ] as $product) {
        test()->postJson(route('showroom.products.store'), $product)->assertCreated();
    }

    test()->postJson(route('showroom.product-models.store'), ['code' => 'polo', 'family_variant' => 'shirts_by_color_size', 'owner' => 'acme-tools-pro'])->assertCreated();
    test()->postJson(route('showroom.product-models.store'), ['code' => 'polo-red', 'parent' => 'polo', 'values' => ['color' => Catalog::value('red')]])->assertCreated();
    test()->postJson(route('showroom.products.store'), ['identifier' => 'VARIANT', 'parent' => 'polo-red', 'values' => ['size' => Catalog::value('m')]])->assertCreated();
}

/**
 * Switch an owner on or off through the API; its products' sync runs inline.
 */
function switchEnabledOwner(string $code, bool $enabled): void
{
    test()->patchJson(route('showroom.owners.update', ['owner' => OwnerModel::query()->where('code', $code)->value('id')]), ['enabled' => $enabled])->assertOk();
}

/**
 * The identifiers a search by effective_enabled answers, sorted.
 *
 * @return list<string>
 */
function productsEffectivelyEnabled(bool $enabled): array
{
    /** @var list<string> $identifiers */
    $identifiers = collect(test()->getJson(route('showroom.products.index', ['effective_enabled' => $enabled ? 1 : 0, 'per_page' => 100]))->assertOk()->json('data'))
        ->pluck('identifier')
        ->sort()
        ->values()
        ->all();

    return $identifiers;
}

dataset('enabled engines', ['database', 'scout']);

it('filters products on effective_enabled, following every owner of the chain', function (string $engine): void {
    searchEffectiveEnabledOn($engine);
    seedEnabledCatalog();

    expect(productsEffectivelyEnabled(true))->toBe(['CHILD', 'GRANDCHILD', 'NO-OWNER', 'ON', 'VARIANT'])
        ->and(productsEffectivelyEnabled(false))->toBe(['OFF']);

    // A middle owner switches off its own products and everything beneath.
    switchEnabledOwner('acme-tools', false);

    expect(productsEffectivelyEnabled(true))->toBe(['NO-OWNER', 'ON'])
        ->and(productsEffectivelyEnabled(false))->toBe(['CHILD', 'GRANDCHILD', 'OFF', 'VARIANT']);

    // The deepest owner switches off only its own, the variant's root model's included.
    switchEnabledOwner('acme-tools', true);
    switchEnabledOwner('acme-tools-pro', false);

    expect(productsEffectivelyEnabled(true))->toBe(['CHILD', 'NO-OWNER', 'ON'])
        ->and(productsEffectivelyEnabled(false))->toBe(['GRANDCHILD', 'OFF', 'VARIANT']);

    // A disabled root reaches everything beneath it; the product's own enabled filter ignores owners.
    switchEnabledOwner('acme-tools-pro', true);
    switchEnabledOwner('acme', false);

    expect(productsEffectivelyEnabled(true))->toBe(['NO-OWNER', 'ON'])
        ->and(collect(test()->getJson(route('showroom.products.index', ['enabled' => 1, 'per_page' => 100]))->json('data'))->pluck('identifier')->sort()->values()->all())
        ->toBe(['CHILD', 'GRANDCHILD', 'NO-OWNER', 'ON', 'VARIANT']);
})->with('enabled engines');

it('indexes effective_enabled beside enabled, and filters on it', function (): void {
    $scout = searchEffectiveEnabledOn('scout');
    seedEnabledCatalog();
    switchEnabledOwner('acme-tools', false);

    expect(collect($scout?->documents)->mapWithKeys(fn (array $document): array => [$document['identifier'] => [$document['enabled'], $document['effective_enabled']]])->sortKeys()->all())->toBe([
        'CHILD' => [true, false],
        'GRANDCHILD' => [true, false],
        'NO-OWNER' => [true, true],
        'OFF' => [false, false],
        'ON' => [true, true],
        'VARIANT' => [true, false],
    ]);

    productsEffectivelyEnabled(true);

    expect(end($scout->searches)->wheres)->toBe([['field' => 'effective_enabled', 'operator' => '=', 'value' => true]]);
});

it('builds effective_enabled into the document from the owner chain it reads', function (): void {
    seedEnabledCatalog();
    switchEnabledOwner('acme', false);

    $products = ProductModel::query()->orderBy('identifier')->get();

    expect(collect(ProductDocument::many($products))->pluck('effective_enabled', 'identifier')->all())->toBe([
        'CHILD' => false,
        'GRANDCHILD' => false,
        'NO-OWNER' => true,
        'OFF' => false,
        'ON' => true,
        'VARIANT' => false,
    ]);
});

it('lists effective_enabled in the Scout index settings', function (): void {
    expect(ScoutEngine::indexSettings()['filterableAttributes'])->toContain('enabled', 'effective_enabled');
});
