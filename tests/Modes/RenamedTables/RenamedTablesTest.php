<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Impex\Domains\Subscription\Actions\CreateSubscriberAction;
use RefactorCircus\Impex\Domains\Subscription\Actions\CreateSubscriptionAction;
use RefactorCircus\Impex\Domains\Subscription\Data\CreateSubscriberData;
use RefactorCircus\Impex\Domains\Subscription\Data\CreateSubscriptionData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Data\Complete;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');

    Catalog::apparel();

    $this->postJson('/showroom/categories', ['code' => 'master'])->assertCreated();
    $this->postJson('/showroom/categories', ['code' => 'tools', 'parent' => 'master'])->assertCreated();
    $this->postJson('/showroom/owner-types', ['code' => 'brand'])->assertCreated();
    $this->postJson('/showroom/owner-types', ['code' => 'series', 'parent_types' => ['brand']])->assertCreated();
    $this->postJson('/showroom/owners', ['code' => 'acme', 'type' => 'brand'])->assertCreated();
    $this->postJson('/showroom/owners', ['code' => 'classic', 'type' => 'series', 'parent' => 'acme'])->assertCreated();
    $this->postJson('/showroom/association-types', ['code' => 'cross_sell'])->assertCreated();
    $this->postJson('/showroom/association-types', ['code' => 'bundle', 'is_quantified' => true])->assertCreated();
});

it('migrates every table under its configured name', function (): void {
    foreach (Tables::DEFAULTS as $key => $default) {
        expect(Schema::hasTable('crm_'.$key))->toBeTrue()
            ->and(Schema::hasTable($default))->toBeFalse();
    }

    expect((new ProductModel)->getTable())->toBe('crm_products')
        ->and(Schema::hasColumn('crm_products', 'changed_at'))->toBeTrue();
});

it('keeps the catalog, its trees, links and unique values working on renamed tables', function (): void {
    $this->postJson('/showroom/product-models', ['code' => 'polo', 'family_variant' => 'shirts_by_color_size', 'owner' => 'classic', 'categories' => ['tools'], 'values' => ['name' => Catalog::value('Polo')]])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'polo-red', 'parent' => 'polo', 'values' => ['color' => Catalog::value('red')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'POLO-RED-M', 'parent' => 'polo-red', 'values' => ['size' => Catalog::value('m'), 'ean' => Catalog::value('1234')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'CAP', 'family' => 'shirts', 'owner' => 'acme', 'categories' => ['master'], 'values' => ['name' => Catalog::value('Cap')]])->assertCreated();

    // The unique attribute's values are held in the renamed table.
    $this->postJson('/showroom/products', ['identifier' => 'POLO-RED-L', 'parent' => 'polo-red', 'values' => ['size' => Catalog::value('l'), 'ean' => Catalog::value('1234')]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('values.ean');

    $this->patchJson('/showroom/products/CAP', [
        'associations' => ['cross_sell' => ['products' => ['POLO-RED-M'], 'product_models' => ['polo']]],
        'quantified_associations' => ['bundle' => ['products' => [['identifier' => 'POLO-RED-M', 'quantity' => 2]]]],
    ])->assertOk()->assertJsonPath('data.associations.cross_sell.products', ['POLO-RED-M']);

    Storage::disk('assets')->put('incoming/front.jpg', 'jpeg-bytes');
    $this->postJson('/showroom/assets', ['code' => 'front', 'path' => 'incoming/front.jpg'])->assertCreated();
    $this->postJson('/showroom/assets/front/links', ['type' => 'product', 'target' => 'CAP', 'role' => 'image'])->assertOk();
    $this->getJson('/showroom/assets?product=CAP&role=image')->assertOk()->assertJsonPath('data.0.code', 'front');

    $this->getJson('/showroom/products/CAP')
        ->assertOk()
        ->assertJsonPath('data.categories', ['master'])
        ->assertJsonPath('data.assets.0.code', 'front')
        ->assertJsonPath('data.quantified_associations.bundle.products.0.quantity', 2);
    $this->getJson('/showroom/products/POLO-RED-M')->assertJsonPath('data.owner', 'classic');

    // Validation rules look in the renamed tables too.
    $this->postJson('/showroom/products', ['identifier' => 'CAP', 'family' => 'nope', 'owner' => 'nobody', 'categories' => ['nowhere']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identifier', 'family', 'owner', 'categories.0']);
    $this->postJson('/showroom/channels', ['code' => 'ecommerce', 'locales' => ['xx']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'locales.0']);
    expect(validator(['scope' => 'ecommerce', 'locale' => 'en'], Complete::rules())->passes())->toBeTrue();

    $this->deleteJson('/showroom/products/CAP')->assertNoContent();
    $this->deleteJson('/showroom/assets/front')->assertNoContent();

    expect(DB::table('crm_asset_links')->count())->toBe(0)
        ->and(DB::table('crm_associations')->count())->toBe(0)
        ->and(DB::table('crm_product_unique_values')->count())->toBe(1);
});

it('searches, scores and versions products on renamed tables', function (): void {
    $this->postJson('/showroom/product-models', ['code' => 'polo', 'family_variant' => 'shirts_by_size', 'owner' => 'classic', 'categories' => ['tools'], 'values' => ['name' => Catalog::value('Green polo')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'POLO-M', 'parent' => 'polo', 'values' => ['size' => Catalog::value('m')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'MUG', 'enabled' => false, 'values' => ['name' => Catalog::value('Mug')]])->assertCreated();

    $identifiers = fn (array $query): array => collect($this->getJson('/showroom/products?'.http_build_query($query))->assertOk()->json('data'))->pluck('identifier')->all();

    expect($identifiers(['search' => 'polo']))->toBe(['POLO-M'])
        ->and($identifiers(['category' => 'master']))->toBe(['POLO-M'])
        ->and($identifiers(['owner' => 'acme']))->toBe(['POLO-M'])
        ->and($identifiers(['parent' => 'polo']))->toBe(['POLO-M'])
        ->and($identifiers(['enabled' => 0]))->toBe(['MUG'])
        ->and($identifiers(['complete' => ['scope' => 'ecommerce', 'locale' => 'en', 'min' => 0]]))->toBe(['POLO-M']);

    $this->postJson('/showroom/products/MUG/transitions', ['transition' => 'submit'])->assertOk();
    $this->postJson('/showroom/products/MUG/transitions', ['transition' => 'approve'])->assertOk();
    $this->postJson('/showroom/products/MUG/transitions', ['transition' => 'publish'])->assertOk();

    $this->getJson('/showroom/products/MUG/versions/published')->assertOk()->assertJsonPath('data.snapshot.values.name.0.data', 'Mug');
    expect($identifiers(['published' => 1]))->toBe(['MUG'])
        ->and(DB::table('crm_versions')->count())->toBeGreaterThan(0)
        ->and(DB::table('crm_product_completeness')->count())->toBeGreaterThan(0);
});

it('pushes published products to webhook subscribers from renamed tables', function (): void {
    config()->set('showroom.impex.webhooks.enabled', true);
    config()->set('showroom.workflow.require_approval', false);
    config()->set('impex.outbound.guard', false);
    app(ImpexIntegration::class)->register();
    Http::fake(['vendor.test/*' => Http::response(['ok' => true])]);

    app(CreateSubscriptionAction::class)->execute(
        app(CreateSubscriberAction::class)->execute(new CreateSubscriberData(name: 'Vendor')),
        CreateSubscriptionData::from(['stream' => 'showroom.products', 'endpoint' => ['url' => 'https://vendor.test/hooks'], 'filter' => ['categories' => ['master'], 'owners' => ['acme']]]),
    );

    $this->postJson('/showroom/products', ['identifier' => 'TEE', 'family' => 'shirts', 'owner' => 'classic', 'categories' => ['tools'], 'values' => ['name' => Catalog::value('Tee')]])->assertCreated();
    $this->postJson('/showroom/products/TEE/transitions', ['transition' => 'publish'])->assertOk();

    $events = collect(Http::recorded())
        ->map(fn (array $pair): Request => $pair[0])
        ->filter(fn (Request $request): bool => ($request->data()['type'] ?? null) === 'events')
        ->flatMap(fn (Request $request): array => $request->data()['events'])
        ->all();

    expect($events)->toHaveCount(1)
        ->and($events[0]['identifier'])->toBe('TEE');
});
