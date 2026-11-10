<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    // manufacturer → vendor → vendor → series; vendors may also stand alone.
    $this->postJson('/showroom/owner-types', ['code' => 'manufacturer', 'owns_products' => false])->assertCreated();
    $this->postJson('/showroom/owner-types', ['code' => 'vendor', 'parent_types' => ['manufacturer']])->assertCreated();
    $this->patchJson('/showroom/owner-types/vendor', ['parent_types' => ['manufacturer', 'vendor']])->assertOk();
    $this->postJson('/showroom/owner-types', ['code' => 'series', 'parent_types' => ['vendor'], 'can_be_root' => false])->assertCreated();
});

function owner(string $code, string $type, ?string $parent = null): void
{
    test()->postJson('/showroom/owners', ['code' => $code, 'type' => $type, 'parent' => $parent])->assertCreated();
}

/**
 * The API path of an owner, addressed by its `id` as routes bind it.
 */
function ownershipOwnerPath(string $code): string
{
    return '/showroom/owners/'.OwnerModel::query()->where('code', $code)->value('id');
}

/**
 * The API path of a product, addressed by its `id` as routes bind it.
 */
function ownershipProductPath(string $identifier): string
{
    return '/showroom/products/'.ProductModel::query()->where('identifier', $identifier)->value('id');
}

it('describes owner types and their rules', function (): void {
    $this->getJson('/showroom/owner-types/series')
        ->assertOk()
        ->assertJsonPath('data.parent_types', ['vendor'])
        ->assertJsonPath('data.can_be_root', false)
        ->assertJsonPath('data.owns_products', true);

    $this->getJson('/showroom/owner-types/manufacturer')->assertJsonPath('data.parent_types', null);
});

it('builds chains of any depth', function (): void {
    owner('globex', 'manufacturer');
    owner('acme', 'vendor', 'globex');
    owner('acme-eu', 'vendor', 'acme');
    owner('classic', 'series', 'acme-eu');
    owner('solo', 'vendor');

    $this->getJson(ownershipOwnerPath('classic'))
        ->assertOk()
        ->assertJsonPath('data.type', 'series')
        ->assertJsonPath('data.parent', 'acme-eu')
        ->assertJsonPath('data.depth', 3)
        ->assertJsonPath('data.chain', ['globex', 'acme', 'acme-eu', 'classic']);

    expect(collect($this->getJson('/showroom/owners?under=acme')->json('data'))->pluck('code')->all())->toBe(['acme-eu', 'classic'])
        ->and(collect($this->getJson('/showroom/owners?roots=1')->json('data'))->pluck('code')->all())->toBe(['globex', 'solo'])
        ->and(collect($this->getJson('/showroom/owners?type=vendor')->json('data'))->pluck('code')->all())->toBe(['acme', 'acme-eu', 'solo']);
});

it('enforces each type\'s parent rules', function (): void {
    owner('globex', 'manufacturer');
    owner('acme', 'vendor');

    $this->postJson('/showroom/owners', ['code' => 'lonely', 'type' => 'series'])
        ->assertUnprocessable()->assertJsonValidationErrors(['parent' => 'A series needs a parent owner.']);

    $this->postJson('/showroom/owners', ['code' => 'odd', 'type' => 'series', 'parent' => 'globex'])
        ->assertUnprocessable()->assertJsonValidationErrors(['parent' => 'A series cannot sit under a manufacturer. Allowed parents: vendor.']);

    $this->postJson('/showroom/owners', ['code' => 'sub', 'type' => 'manufacturer', 'parent' => 'acme'])->assertCreated();
});

it('moves an owner with everything beneath it', function (): void {
    owner('acme', 'vendor');
    owner('acme-eu', 'vendor', 'acme');
    owner('classic', 'series', 'acme-eu');
    owner('initech', 'vendor');

    $this->patchJson(ownershipOwnerPath('acme-eu'), ['parent' => 'initech'])->assertOk()->assertJsonPath('data.parent', 'initech');

    $this->getJson(ownershipOwnerPath('classic'))
        ->assertJsonPath('data.chain', ['initech', 'acme-eu', 'classic'])
        ->assertJsonPath('data.depth', 2);

    // Moving to the root.
    $this->patchJson(ownershipOwnerPath('acme-eu'), ['parent' => null])->assertOk();
    $this->getJson(ownershipOwnerPath('classic'))->assertJsonPath('data.chain', ['acme-eu', 'classic'])->assertJsonPath('data.depth', 1);
});

it('refuses cycles', function (): void {
    owner('acme', 'vendor');
    owner('acme-eu', 'vendor', 'acme');

    $this->patchJson(ownershipOwnerPath('acme'), ['parent' => 'acme-eu'])->assertUnprocessable()->assertJsonValidationErrors('parent');
    $this->patchJson(ownershipOwnerPath('acme'), ['parent' => 'acme'])->assertUnprocessable()->assertJsonValidationErrors('parent');
});

it('never changes an owner\'s code or type', function (): void {
    owner('acme', 'vendor');

    $this->patchJson(ownershipOwnerPath('acme'), ['code' => 'x', 'type' => 'series'])
        ->assertUnprocessable()->assertJsonValidationErrors(['code', 'type']);
});

it('assigns products and root models to owners that own products', function (): void {
    Catalog::apparel();
    owner('globex', 'manufacturer');
    owner('acme', 'vendor', 'globex');
    owner('classic', 'series', 'acme');

    $this->postJson('/showroom/products', ['identifier' => 'TEE-1', 'owner' => 'classic'])
        ->assertCreated()->assertJsonPath('data.owner', 'classic');

    $this->postJson('/showroom/products', ['identifier' => 'TEE-2', 'owner' => 'globex'])
        ->assertUnprocessable()->assertJsonValidationErrors(['owner' => 'Owner "globex" is a manufacturer, which does not own products.']);

    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_color_size', 'owner' => 'acme'])
        ->assertCreated()->assertJsonPath('data.owner', 'acme');

    $this->postJson('/showroom/product-models', ['code' => 'tee-red', 'parent' => 'tee', 'owner' => 'acme', 'values' => ['color' => Catalog::value('red')]])
        ->assertUnprocessable()->assertJsonValidationErrors('owner');

    $this->postJson('/showroom/product-models', ['code' => 'tee-red', 'parent' => 'tee', 'values' => ['color' => Catalog::value('red')]])
        ->assertCreated()->assertJsonPath('data.owner', 'acme');

    // Variants take their root model's owner.
    $this->postJson('/showroom/products', ['identifier' => 'TEE-RED-M', 'parent' => 'tee-red', 'owner' => 'classic', 'values' => ['size' => Catalog::value('m')]])
        ->assertUnprocessable()->assertJsonValidationErrors('owner');

    $this->postJson('/showroom/products', ['identifier' => 'TEE-RED-M', 'parent' => 'tee-red', 'values' => ['size' => Catalog::value('m')]])
        ->assertCreated()->assertJsonPath('data.owner', 'acme');

    $this->patchJson('/showroom/product-models/tee', ['owner' => 'classic'])->assertOk();
    $this->getJson(ownershipProductPath('TEE-RED-M'))->assertJsonPath('data.owner', 'classic');

    $this->patchJson(ownershipProductPath('TEE-1'), ['owner' => null])->assertOk()->assertJsonPath('data.owner', null);
});

it('finds products by owner, including everything beneath it', function (): void {
    Catalog::apparel();
    owner('acme', 'vendor');
    owner('classic', 'series', 'acme');
    owner('initech', 'vendor');

    $this->postJson('/showroom/products', ['identifier' => 'DIRECT', 'owner' => 'acme'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'SERIES', 'owner' => 'classic'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'OTHER', 'owner' => 'initech'])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_size', 'owner' => 'classic'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'VARIANT', 'parent' => 'tee', 'values' => ['size' => Catalog::value('m')]])->assertCreated();

    $identifiers = fn (string $owner): array => collect($this->getJson('/showroom/products?owner='.$owner)->json('data'))->pluck('identifier')->all();

    expect($identifiers('acme'))->toBe(['DIRECT', 'SERIES', 'VARIANT'])
        ->and($identifiers('classic'))->toBe(['SERIES', 'VARIANT'])
        ->and($identifiers('initech'))->toBe(['OTHER'])
        ->and($identifiers('nobody'))->toBe([]);
});

it('refuses to delete owners and types still in use', function (): void {
    owner('acme', 'vendor');
    owner('classic', 'series', 'acme');

    $this->deleteJson(ownershipOwnerPath('acme'))
        ->assertConflict()
        ->assertJsonPath('message', 'Owner "acme" still has 1 child owner(s) and 0 product(s) or product model(s). Move or delete them first.');

    $this->postJson('/showroom/products', ['identifier' => 'P', 'owner' => 'classic'])->assertCreated();
    $this->deleteJson(ownershipOwnerPath('classic'))->assertConflict();

    $this->deleteJson('/showroom/owner-types/series')->assertConflict();

    $this->deleteJson(ownershipProductPath('P'))->assertNoContent();
    $this->deleteJson(ownershipOwnerPath('classic'))->assertNoContent();
    $this->deleteJson('/showroom/owner-types/series')->assertNoContent();

    expect(OwnerModel::query()->pluck('code')->all())->toBe(['acme']);
});
