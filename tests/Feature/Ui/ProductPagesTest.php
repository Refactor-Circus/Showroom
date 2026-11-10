<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyVariantModel;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    Catalog::apparel();
});

/**
 * A product by its identifier; pages address it by its lookup key.
 */
function uiProduct(string $identifier): ProductModel
{
    return ProductModel::query()->where('identifier', $identifier)->firstOrFail();
}

/**
 * The flags of the product page tests, an owner carrying one, and a product
 * beneath it with an own flag and an external id.
 */
function uiFlaggedProduct(): ProductModel
{
    FlagModel::factory()->create(['code' => 'pricing_only', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 2]);
    FlagModel::factory()->create(['code' => 'clearance', 'applies_to' => ['product'], 'sort_order' => 3]);
    FlagModel::factory()->create(['code' => 'preferred', 'applies_to' => ['owner'], 'sort_order' => 4]);

    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor', 'flags' => ['pricing_only']]));

    return app(CreateProductAction::class)->execute(CreateProductData::from([
        'identifier' => 'TEE-1',
        'owner' => 'acme',
        'flags' => ['clearance'],
        'external_ids' => ['erp' => 'E-1'],
    ]));
}

/**
 * Turn authorization on with a flag policy refusing `assign`, and sign in.
 */
function uiSignInWithoutAssign(): void
{
    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, ReadOnlyFlagPolicy::class);

    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));

    test()->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));
}

it('creates a product and edits its values', function (): void {
    $this->get(route('atrium.showroom.products.create'))->assertOk()->assertSee('shirts');

    $this->post(route('atrium.showroom.products.store'), ['identifier' => 'TEE-1', 'family' => 'shirts'])
        ->assertRedirect(route('atrium.showroom.products.show', uiProduct('TEE-1')));

    $this->get(route('atrium.showroom.products.show', uiProduct('TEE-1')))
        ->assertOk()
        ->assertSee('data-attribute="weight"', false)
        ->assertSee('data-attribute="tags"', false);

    $this->patch(route('atrium.showroom.products.update', uiProduct('TEE-1')), [
        'family' => 'shirts',
        'enabled' => '0',
        'v' => [
            'name' => 'Classic tee',
            'description' => 'Soft',
            'color' => 'red',
            'tags' => ['', 'summer', 'new'],
            'organic' => '1',
            'weight' => ['amount' => '180', 'unit' => 'gram'],
            'price' => ['USD' => '19.99', 'EUR' => ''],
            'pack_size' => '',
        ],
    ])->assertRedirect(route('atrium.showroom.products.show', ['product' => uiProduct('TEE-1'), 'locale' => 'en', 'channel' => 'ecommerce']))->assertSessionHasNoErrors();

    $product = uiProduct('TEE-1');

    expect($product->enabled)->toBeFalse()
        ->and($product->value('name'))->toBe('Classic tee')
        ->and($product->value('description', null, 'en'))->toBe('Soft')
        ->and($product->value('tags'))->toBe(['summer', 'new'])
        ->and($product->value('organic'))->toBeTrue()
        ->and($product->value('weight'))->toBe(['amount' => '180', 'unit' => 'gram'])
        ->and($product->value('price'))->toBe([['amount' => '19.99', 'currency' => 'USD']])
        ->and($product->value('pack_size'))->toBeNull();

    $this->get(route('atrium.showroom.products.index', ['search' => 'TEE']))->assertOk()->assertSee('TEE-1')->assertSee('Classic tee');
});

it('shows validation errors next to the value', function (): void {
    $this->post(route('atrium.showroom.products.store'), ['identifier' => 'TEE-1', 'family' => 'shirts']);

    $this->from(route('atrium.showroom.products.show', uiProduct('TEE-1')))
        ->patch(route('atrium.showroom.products.update', uiProduct('TEE-1')), ['v' => ['pack_size' => '0']])
        ->assertSessionHasErrors('values.pack_size.0.data');
});

it('adds attributes one at a time to a product without a family', function (): void {
    $this->post(route('atrium.showroom.products.store'), ['identifier' => 'LOOSE']);

    $this->get(route('atrium.showroom.products.show', ['product' => uiProduct('LOOSE'), 'add' => 'color']))
        ->assertOk()
        ->assertSee('data-attribute="color"', false)
        ->assertDontSee('data-attribute="size"', false);

    $this->patch(route('atrium.showroom.products.update', uiProduct('LOOSE')), ['add' => 'color', 'v' => ['color' => 'blue']])->assertSessionHasNoErrors();

    expect(uiProduct('LOOSE')->value('color'))->toBe('blue');
});

it('builds a variant tree from the dashboard', function (): void {
    $this->get(route('atrium.showroom.product-models.index'))->assertOk()->assertSee('shirts_by_color_size');

    $this->post(route('atrium.showroom.product-models.store'), ['code' => 'tee', 'family_variant' => 'shirts_by_color_size'])
        ->assertRedirect(route('atrium.showroom.product-models.show', 'tee'));

    $this->get(route('atrium.showroom.product-models.show', 'tee'))
        ->assertOk()
        ->assertSee(__('showroom::showroom.add_sub_model'))
        ->assertSee('data-attribute="name"', false);

    $this->post(route('atrium.showroom.product-models.store'), [
        'code' => 'tee-red',
        'parent' => 'tee',
        'values' => ['color' => [['locale' => '', 'scope' => '', 'data' => 'red']]],
    ])->assertRedirect(route('atrium.showroom.product-models.show', 'tee-red'));

    $this->get(route('atrium.showroom.product-models.show', 'tee-red'))->assertOk()->assertSee(__('showroom::showroom.add_variant'));

    $this->post(route('atrium.showroom.products.store'), [
        'identifier' => 'TEE-RED-M',
        'parent' => 'tee-red',
        'values' => ['size' => [['locale' => '', 'scope' => '', 'data' => 'm']]],
    ])->assertRedirect(route('atrium.showroom.products.show', uiProduct('TEE-RED-M')));

    $this->patch(route('atrium.showroom.product-models.update', 'tee'), ['v' => ['name' => 'Classic tee']])->assertSessionHasNoErrors();

    $this->get(route('atrium.showroom.products.show', uiProduct('TEE-RED-M')))
        ->assertOk()
        ->assertSee(__('showroom::showroom.inherited_values'))
        ->assertSee('Classic tee')
        ->assertDontSee('name="family"', false);

    $this->delete(route('atrium.showroom.product-models.destroy', 'tee-red'))->assertRedirect(route('atrium.showroom.product-models.show', 'tee'));

    expect(ProductModelModel::query()->count())->toBe(1)->and(ProductModel::query()->count())->toBe(0);
});

it('creates and deletes a family variant from the family page', function (): void {
    $this->get(route('atrium.showroom.families.show', 'shirts'))->assertOk()->assertSee('shirts_by_size');

    $this->post(route('atrium.showroom.family-variants.store'), [
        'family' => 'shirts',
        'code' => 'shirts_by_organic',
        'levels' => [
            ['axes' => ['organic'], 'attributes' => ['ean']],
            ['axes' => [], 'attributes' => []],
        ],
    ])->assertRedirect(route('atrium.showroom.family-variants.show', 'shirts_by_organic'));

    $this->get(route('atrium.showroom.family-variants.show', 'shirts_by_organic'))->assertOk()->assertSee('organic');

    expect(FamilyVariantModel::query()->where('code', 'shirts_by_organic')->value('levels'))->toBe(1);

    $this->delete(route('atrium.showroom.family-variants.destroy', 'shirts_by_organic'))
        ->assertRedirect(route('atrium.showroom.families.show', 'shirts'));
});

it('keeps the products page usable when the search engine refuses a query', function (): void {
    $this->get(route('atrium.showroom.products.index', ['filters' => [['attribute' => 'price', 'operator' => '=', 'value' => '1']]]))
        ->assertOk()
        ->assertSee('cannot filter attribute');
});

describe('slug, external ids and flags', function (): void {
    it('shows the slug read-only', function (): void {
        $product = uiFlaggedProduct();

        $this->get(route('atrium.showroom.products.show', $product))
            ->assertOk()
            ->assertSee('data-testid="product-identity"', false)
            ->assertSeeInOrder(['data-testid="product-slug"', 'tee-1'], false)
            ->assertDontSee('name="slug"', false);
    });

    it('edits the external ids as a map: change, add and remove a system', function (): void {
        $product = uiFlaggedProduct();

        $this->get(route('atrium.showroom.products.show', $product))
            ->assertOk()
            ->assertSee('data-testid="external-ids"', false)
            ->assertSee('name="external_ids[erp]"', false)
            ->assertSee('value="E-1"', false)
            ->assertSee('name="external_ids_new[system]"', false)
            ->assertSee('name="external_ids_new[id]"', false);

        $this->patch(route('atrium.showroom.products.update', $product), [
            'external_ids' => ['erp' => 'E-2'],
            'external_ids_new' => ['system' => 'd365', 'id' => 'D-1'],
        ])->assertSessionHasNoErrors();

        expect(ExternalIds::present(uiProduct('TEE-1')->externalIds))->toBe(['d365' => 'D-1', 'erp' => 'E-2']);

        $this->patch(route('atrium.showroom.products.update', $product), [
            'external_ids' => ['d365' => 'D-1', 'erp' => ''],
            'external_ids_new' => ['system' => '', 'id' => ''],
        ])->assertSessionHasNoErrors();

        expect(ExternalIds::present(uiProduct('TEE-1')->externalIds))->toBe(['d365' => 'D-1']);
    });

    it('shows an external id held by another product as the error', function (): void {
        $product = uiFlaggedProduct();
        app(CreateProductAction::class)->execute(CreateProductData::from(['identifier' => 'TEE-2', 'external_ids' => ['erp' => 'E-9']]));

        $this->from(route('atrium.showroom.products.show', $product))
            ->patch(route('atrium.showroom.products.update', $product), ['external_ids' => ['erp' => 'E-9']])
            ->assertSessionHasErrors('external_ids.erp');

        expect(ExternalIds::present(uiProduct('TEE-1')->externalIds))->toBe(['erp' => 'E-1']);
    });

    it('offers as own flags only the flags that apply to products, with the own ones ticked', function (): void {
        $product = uiFlaggedProduct();

        $html = (string) $this->get(route('atrium.showroom.products.show', $product))
            ->assertOk()
            ->assertSee('data-testid="own-flags"', false)
            ->assertSee('name="flags_sent"', false)
            ->getContent();

        expect($html)->toContain('id="atrium-flags-pricing_only"')
            ->toContain('id="atrium-flags-restricted"')
            ->toContain('id="atrium-flags-clearance"')
            ->not->toContain('id="atrium-flags-preferred"')
            ->and(preg_match('/id="atrium-flags-clearance"[^>]*value="clearance"\s+checked\s/', $html))->toBe(1)
            ->and(preg_match('/id="atrium-flags-restricted"[^>]*value="restricted"\s+checked\s/', $html))->toBe(0);
    });

    it('shows the effective flags read-only, marking the inherited ones', function (): void {
        $product = uiFlaggedProduct();

        $this->get(route('atrium.showroom.products.show', $product))
            ->assertOk()
            ->assertSee('data-testid="effective-flags"', false)
            ->assertSee('data-effective-flag="pricing_only" data-inherited="true"', false)
            ->assertSee('data-effective-flag="clearance" data-inherited="false"', false)
            ->assertDontSee('data-effective-flag="restricted"', false)
            ->assertDontSee('name="effective_flags', false);
    });

    it('replaces the own flags from the page, and clears them when none is ticked', function (): void {
        $product = uiFlaggedProduct();

        $this->patch(route('atrium.showroom.products.update', $product), ['flags_sent' => '1', 'flags' => ['restricted', 'clearance']])
            ->assertSessionHasNoErrors();

        expect(uiProduct('TEE-1')->flags()->pluck('code')->sort()->values()->all())->toBe(['clearance', 'restricted']);

        $this->patch(route('atrium.showroom.products.update', $product), ['flags_sent' => '1'])->assertSessionHasNoErrors();

        expect(uiProduct('TEE-1')->flags()->count())->toBe(0);
    });

    it('leaves the own flags alone when the form does not send them', function (): void {
        $product = uiFlaggedProduct();

        $this->patch(route('atrium.showroom.products.update', $product), ['enabled' => '0'])->assertSessionHasNoErrors();

        expect(uiProduct('TEE-1')->flags()->pluck('code')->all())->toBe(['clearance']);
    });

    it('hides the own-flags field without assign, and still shows the effective flags', function (): void {
        $product = uiFlaggedProduct();
        uiSignInWithoutAssign();

        $this->get(route('atrium.showroom.products.show', $product))
            ->assertOk()
            ->assertDontSee('data-testid="own-flags"', false)
            ->assertDontSee('name="flags[]"', false)
            ->assertDontSee('name="flags_sent"', false)
            ->assertSee('data-effective-flag="clearance" data-inherited="false"', false)
            ->assertSee('data-effective-flag="pricing_only" data-inherited="true"', false)
            ->assertSee('name="external_ids[erp]"', false);

        // The rest of the page still saves; a flag change is refused.
        $this->patch(route('atrium.showroom.products.update', $product), ['external_ids' => ['erp' => 'E-5']])->assertSessionHasNoErrors();
        $this->patch(route('atrium.showroom.products.update', $product), ['flags_sent' => '1', 'flags' => ['restricted']])->assertForbidden();

        expect(uiProduct('TEE-1')->flags()->pluck('code')->all())->toBe(['clearance'])
            ->and(ExternalIds::present(uiProduct('TEE-1')->externalIds))->toBe(['erp' => 'E-5']);
    });
});

describe('links', function (): void {
    it('links products by id by default', function (): void {
        $product = uiFlaggedProduct();

        $this->get(route('atrium.showroom.products.index'))
            ->assertOk()
            ->assertSee('href="'.route('atrium.showroom.products.show', $product->id).'"', false);
    });

    it('links products by slug when the lookup key is slug', function (): void {
        $product = uiFlaggedProduct();
        config()->set('showroom.lookup.products', 'slug');

        $this->get(route('atrium.showroom.products.index'))
            ->assertOk()
            ->assertSee('href="'.route('atrium.showroom.products.show', 'tee-1').'"', false);

        $this->get(route('atrium.showroom.products.show', 'tee-1'))
            ->assertOk()
            ->assertSee('action="'.route('atrium.showroom.products.update', 'tee-1').'"', false);

        expect($product->slug)->toBe('tee-1');
    });
});
