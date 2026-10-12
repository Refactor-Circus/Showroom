<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    Catalog::apparel();
});

it('answers 304 to a client that already has the product', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'TEE-1', 'values' => ['name' => Catalog::value('Tee')]])->assertCreated();

    $etag = $this->getJson('/showroom/products/TEE-1')->assertOk()->headers->get('ETag');

    expect($etag)->not->toBeNull();

    $this->getJson('/showroom/products/TEE-1', ['If-None-Match' => (string) $etag])
        ->assertStatus(304)
        ->assertContent('');

    $this->patchJson('/showroom/products/TEE-1', ['values' => ['name' => Catalog::value('New tee')]])->assertOk();

    $this->getJson('/showroom/products/TEE-1', ['If-None-Match' => (string) $etag])
        ->assertOk()
        ->assertJsonPath('data.values.name.0.data', 'New tee');
});

it('lists only products changed since a moment', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'OLD'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'NEW'])->assertCreated();

    DB::table(Tables::name('products'))->where('identifier', 'OLD')->update(['changed_at' => now()->subDay()]);

    $this->getJson('/showroom/products?updated_since='.urlencode(now()->subHour()->toIso8601String()))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.identifier', 'NEW');
});

it('filters by updated_since over MCP too', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'OLD'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'NEW'])->assertCreated();

    DB::table(Tables::name('products'))->where('identifier', 'OLD')->update(['changed_at' => now()->subDay()]);

    mcpTool(ListProductsTool::class, ['updated_since' => now()->subHour()->toIso8601String()])
        ->assertOk()
        ->assertSee('NEW')
        ->assertDontSee('OLD');
});

it('counts a change a product inherits from its model as a change', function (): void {
    $this->postJson('/showroom/categories', ['code' => 'shirts'])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'polo', 'family_variant' => 'shirts_by_size'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'POLO-M', 'parent' => 'polo', 'values' => ['size' => Catalog::value('m')]])->assertCreated();

    DB::table(Tables::name('products'))->where('identifier', 'POLO-M')->update(['changed_at' => now()->subDay(), 'updated_at' => now()->subDay()]);
    $since = urlencode(now()->subHour()->toIso8601String());

    $this->getJson('/showroom/products?updated_since='.$since)->assertJsonCount(0, 'data');

    // The variant's own row is untouched; what it shows is not.
    $this->patchJson('/showroom/product-models/polo', ['categories' => ['shirts']])->assertOk();

    $this->getJson('/showroom/products?updated_since='.$since)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.identifier', 'POLO-M');

    expect(DB::table(Tables::name('products'))->where('identifier', 'POLO-M')->value('updated_at'))->toBeLessThan(now()->subHour()->toDateTimeString());
});
