<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(fn () => Catalog::apparel());

/**
 * The API path of a product, addressed by its `id` as routes bind it.
 */
function productApiPath(string $identifier): string
{
    return '/showroom/products/'.ProductModel::query()->where('identifier', $identifier)->value('id');
}

it('creates a simple product with typed values', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-001',
        'family' => 'shirts',
        'values' => [
            'name' => Catalog::value('Classic tee'),
            'description' => Catalog::value('Soft cotton', 'en'),
            'weight' => Catalog::value(['amount' => 180, 'unit' => 'gram']),
            'price' => Catalog::value([['amount' => 19.99, 'currency' => 'USD']]),
            'pack_size' => Catalog::value('3'),
            'rating' => Catalog::value(4.5),
            'organic' => Catalog::value(true),
            'released' => Catalog::value('2026-03-01'),
            'color' => Catalog::value('red'),
            'tags' => Catalog::value(['summer', 'new']),
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.identifier', 'TEE-001')
        ->assertJsonPath('data.family', 'shirts')
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.values.name.0.data', 'Classic tee')
        ->assertJsonPath('data.values.description.0.locale', 'en')
        ->assertJsonPath('data.values.weight.0.data', ['amount' => '180', 'unit' => 'gram'])
        ->assertJsonPath('data.values.price.0.data', [['amount' => '19.99', 'currency' => 'USD']])
        ->assertJsonPath('data.values.pack_size.0.data', 3)
        ->assertJsonPath('data.values.rating.0.data', '4.5')
        ->assertJsonPath('data.values.organic.0.data', true)
        ->assertJsonPath('data.values.tags.0.data', ['summer', 'new']);
});

it('validates each value against its attribute', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-001',
        'values' => [
            'color' => Catalog::value('purple'),
            'tags' => Catalog::value(['summer', 'winter']),
            'pack_size' => Catalog::value(0),
            'rating' => Catalog::value(4.55),
            'released' => Catalog::value('March 1st'),
            'price' => Catalog::value([['amount' => 5, 'currency' => 'GBP']]),
            'weight' => Catalog::value(['amount' => 5]),
            'organic' => Catalog::value('maybe'),
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'values.color.0.data',
            'values.tags.0.data.1',
            'values.pack_size.0.data',
            'values.rating.0.data',
            'values.released.0.data',
            'values.price.0.data.0.currency',
            'values.weight.0.data.unit',
            'values.organic.0.data',
        ]);
});

it('checks locale and scope against the attribute', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-001',
        'values' => [
            'description' => Catalog::value('No locale'),
            'name' => Catalog::value('Localized name', 'en'),
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors(['values.description.0.locale', 'values.name.0.locale']);
});

it('refuses unknown attributes, and lets a product with a family set any other', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'X-1', 'values' => ['nope' => Catalog::value('x')]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values.nope' => 'Attribute "nope" does not exist.']);

    $this->postJson('/showroom/families', ['code' => 'mugs', 'attributes' => [['attribute' => 'name']]])->assertCreated();

    $this->postJson('/showroom/products', ['identifier' => 'MUG-1', 'family' => 'mugs', 'values' => ['nope' => Catalog::value('x')]])
        ->assertUnprocessable()->assertJsonValidationErrors('values.nope');

    // The family sets the completeness standard, not which attributes a product holds.
    $this->postJson('/showroom/products', ['identifier' => 'MUG-1', 'family' => 'mugs', 'values' => ['color' => Catalog::value('red')]])
        ->assertCreated()
        ->assertJsonPath('data.values.color.0.data', 'red');

    $this->patchJson(productApiPath('MUG-1'), ['values' => ['organic' => Catalog::value(true)]])
        ->assertOk()
        ->assertJsonPath('data.values.color.0.data', 'red')
        ->assertJsonPath('data.values.organic.0.data', true);

    $this->getJson(productApiPath('MUG-1'))
        ->assertOk()
        ->assertJsonPath('data.family', 'mugs')
        ->assertJsonPath('data.values.color.0.data', 'red')
        ->assertJsonPath('data.values.organic.0.data', true);

    // Without a family, any attribute may be set too.
    $this->postJson('/showroom/products', ['identifier' => 'LOOSE-1', 'values' => ['color' => Catalog::value('red')]])->assertCreated();
});

it('hides no value when the family changes', function (): void {
    $this->postJson('/showroom/families', ['code' => 'mugs', 'attributes' => [['attribute' => 'name']]])->assertCreated();

    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-1',
        'family' => 'shirts',
        'values' => ['name' => Catalog::value('Tee'), 'color' => Catalog::value('red')],
    ])->assertCreated();

    $this->patchJson(productApiPath('TEE-1'), ['family' => 'mugs'])
        ->assertOk()
        ->assertJsonPath('data.family', 'mugs')
        ->assertJsonPath('data.values.name.0.data', 'Tee')
        ->assertJsonPath('data.values.color.0.data', 'red');

    $this->getJson('/showroom/products?family=mugs')
        ->assertOk()
        ->assertJsonPath('data.0.identifier', 'TEE-1')
        ->assertJsonPath('data.0.values.color.0.data', 'red');

    // And it still edits them under the new family.
    $this->patchJson(productApiPath('TEE-1'), ['values' => ['color' => Catalog::value('blue')]])
        ->assertOk()
        ->assertJsonPath('data.values.color.0.data', 'blue');
});

it('patches only the slots sent, and clears a slot with null', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-001',
        'values' => ['name' => Catalog::value('Tee'), 'description' => Catalog::value('Soft', 'en'), 'color' => Catalog::value('red')],
    ])->assertCreated();

    $this->patchJson(productApiPath('TEE-001'), [
        'values' => ['description' => Catalog::value('Doux', 'fr'), 'color' => Catalog::value(null)],
    ])
        ->assertOk()
        ->assertJsonPath('data.values.name.0.data', 'Tee')
        ->assertJsonCount(2, 'data.values.description')
        ->assertJsonMissingPath('data.values.color');
});

it('never changes the identifier', function (): void {
    ProductModel::factory()->create(['identifier' => 'TEE-001']);

    $this->patchJson(productApiPath('TEE-001'), ['identifier' => 'TEE-002'])->assertUnprocessable()->assertJsonValidationErrors('identifier');
});

it('enforces unique attributes across products', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'A', 'values' => ['ean' => Catalog::value('4006381333931')]])->assertCreated();

    $this->postJson('/showroom/products', ['identifier' => 'B', 'values' => ['ean' => Catalog::value('4006381333931')]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values.ean' => 'The value "4006381333931" of unique attribute "ean" is already used by product "A".']);

    expect(ProductModel::query()->where('identifier', 'B')->exists())->toBeFalse();

    // Freed when A lets go of it.
    $this->patchJson(productApiPath('A'), ['values' => ['ean' => Catalog::value(null)]])->assertOk();
    $this->postJson('/showroom/products', ['identifier' => 'B', 'values' => ['ean' => Catalog::value('4006381333931')]])->assertCreated();
});

it('refuses unique attributes that are localizable or scopable', function (): void {
    $this->postJson('/showroom/attributes', ['code' => 'gtin', 'type' => 'text', 'is_unique' => true, 'is_localizable' => true])
        ->assertUnprocessable()->assertJsonValidationErrors('is_unique');

    $this->patchJson('/showroom/attributes/ean', ['is_scopable' => true])
        ->assertUnprocessable()->assertJsonValidationErrors('is_unique');
});

it('builds variant products that inherit from their models', function (): void {
    $this->postJson('/showroom/product-models', [
        'code' => 'tee',
        'family_variant' => 'shirts_by_color_size',
        'values' => ['name' => Catalog::value('Classic tee')],
    ])->assertCreated()->assertJsonPath('data.level', 0);

    $this->postJson('/showroom/product-models', [
        'code' => 'tee-red',
        'parent' => 'tee',
        'values' => ['color' => Catalog::value('red'), 'price' => Catalog::value([['amount' => 20, 'currency' => 'USD']])],
    ])
        ->assertCreated()
        ->assertJsonPath('data.level', 1)
        ->assertJsonPath('data.family_variant', 'shirts_by_color_size')
        ->assertJsonPath('data.values.name.0.data', 'Classic tee');

    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-RED-M',
        'parent' => 'tee-red',
        'values' => ['size' => Catalog::value('m'), 'ean' => Catalog::value('111')],
    ])
        ->assertCreated()
        ->assertJsonPath('data.family', 'shirts')
        ->assertJsonPath('data.parent', 'tee-red')
        ->assertJsonPath('data.values.name.0.data', 'Classic tee')
        ->assertJsonPath('data.values.color.0.data', 'red')
        ->assertJsonPath('data.values.size.0.data', 'm');

    // A change on the root model reaches the variant.
    $this->patchJson('/showroom/product-models/tee', ['values' => ['name' => Catalog::value('Organic tee')]])->assertOk();

    $this->getJson(productApiPath('TEE-RED-M'))->assertJsonPath('data.values.name.0.data', 'Organic tee');

    $this->getJson('/showroom/product-models/tee')
        ->assertJsonPath('data.children', ['tee-red'])
        ->assertJsonPath('data.products', []);
});

it('limits each level to its own attributes', function (): void {
    $this->postJson('/showroom/product-models', [
        'code' => 'tee',
        'family_variant' => 'shirts_by_color_size',
        'values' => ['color' => Catalog::value('red')],
    ])->assertUnprocessable()->assertJsonValidationErrors('values.color');

    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_color_size'])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'tee-red', 'parent' => 'tee', 'values' => ['color' => Catalog::value('red')]])->assertCreated();

    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-RED-M',
        'parent' => 'tee-red',
        'values' => ['size' => Catalog::value('m'), 'name' => Catalog::value('Mine')],
    ])->assertUnprocessable()->assertJsonValidationErrors('values.name');
});

it('keeps the level rules for attributes outside the family', function (): void {
    // Outside the family, an attribute has no level of the family variant.
    $this->postJson('/showroom/attributes', ['code' => 'fabric', 'type' => 'text'])->assertCreated();

    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_color_size', 'values' => ['fabric' => Catalog::value('Cotton')]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values.fabric' => 'Attribute "fabric" is not set at level 0 of family variant "shirts_by_color_size".']);

    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_color_size'])->assertCreated();

    $this->postJson('/showroom/product-models', ['code' => 'tee-red', 'parent' => 'tee', 'values' => ['color' => Catalog::value('red'), 'fabric' => Catalog::value('Cotton')]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values.fabric' => 'Attribute "fabric" is not set at level 1 of family variant "shirts_by_color_size".']);

    $this->postJson('/showroom/product-models', ['code' => 'tee-red', 'parent' => 'tee', 'values' => ['color' => Catalog::value('red')]])->assertCreated();

    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-RED-M',
        'parent' => 'tee-red',
        'values' => ['size' => Catalog::value('m'), 'fabric' => Catalog::value('Cotton')],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['values.fabric' => 'Attribute "fabric" is not set on variant products of family variant "shirts_by_color_size".']);

    // A simple product of the same family may hold it.
    $this->postJson('/showroom/products', ['identifier' => 'TEE-1', 'family' => 'shirts', 'values' => ['fabric' => Catalog::value('Cotton')]])
        ->assertCreated()
        ->assertJsonPath('data.values.fabric.0.data', 'Cotton');
});

it('requires every axis and a distinct combination', function (): void {
    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_size'])->assertCreated();

    $this->postJson('/showroom/products', ['identifier' => 'TEE-M', 'parent' => 'tee'])
        ->assertUnprocessable()->assertJsonValidationErrors('values.size');

    $this->postJson('/showroom/products', ['identifier' => 'TEE-M', 'parent' => 'tee', 'values' => ['size' => Catalog::value('m')]])->assertCreated();

    $this->postJson('/showroom/products', ['identifier' => 'TEE-M2', 'parent' => 'tee', 'values' => ['size' => Catalog::value('m')]])
        ->assertUnprocessable()->assertJsonValidationErrors('values');

    $this->postJson('/showroom/products', ['identifier' => 'TEE-L', 'parent' => 'tee', 'values' => ['size' => Catalog::value('l')]])->assertCreated();

    // Moving a sibling onto a taken combination is refused too.
    $this->patchJson(productApiPath('TEE-L'), ['values' => ['size' => Catalog::value('m')]])
        ->assertUnprocessable()->assertJsonValidationErrors('values');
});

it('puts variant products only under the last model level', function (): void {
    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_color_size'])->assertCreated();

    $this->postJson('/showroom/products', ['identifier' => 'TEE-M', 'parent' => 'tee', 'values' => ['size' => Catalog::value('m')]])
        ->assertUnprocessable()->assertJsonValidationErrors('parent');

    $this->postJson('/showroom/product-models', ['code' => 'solo', 'family_variant' => 'shirts_by_size'])->assertCreated();

    $this->postJson('/showroom/product-models', ['code' => 'solo-red', 'parent' => 'solo'])
        ->assertUnprocessable()->assertJsonValidationErrors('parent');
});

it('refuses a family change on a variant product', function (): void {
    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_size'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'TEE-M', 'parent' => 'tee', 'values' => ['size' => Catalog::value('m')]])->assertCreated();

    $this->patchJson(productApiPath('TEE-M'), ['family' => null])->assertUnprocessable()->assertJsonValidationErrors('family');
});

it('deletes a model with its sub-models and variant products', function (): void {
    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_color_size'])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'tee-red', 'parent' => 'tee', 'values' => ['color' => Catalog::value('red')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'TEE-RED-M', 'parent' => 'tee-red', 'values' => ['size' => Catalog::value('m'), 'ean' => Catalog::value('1')]])->assertCreated();

    $this->deleteJson('/showroom/product-models/tee')->assertNoContent();

    expect(ProductModel::query()->count())->toBe(0);

    // Its unique values went with it.
    $this->postJson('/showroom/products', ['identifier' => 'OTHER', 'values' => ['ean' => Catalog::value('1')]])->assertCreated();
});

it('refuses to delete a family with products', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'TEE-1', 'family' => 'shirts'])->assertCreated();

    $this->deleteJson('/showroom/families/shirts')
        ->assertConflict()
        ->assertJsonPath('message', 'Family "shirts" still has 1 product(s) and 0 product model(s). Delete or move them first.');
});

it('purges the values of a deleted attribute or option', function (): void {
    $this->postJson('/showroom/attributes', ['code' => 'fabric', 'type' => 'text'])->assertCreated();

    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-1',
        'values' => ['fabric' => Catalog::value('cotton'), 'tags' => Catalog::value(['summer', 'sale']), 'color' => Catalog::value('red')],
    ])->assertCreated();

    $this->deleteJson('/showroom/attributes/fabric')->assertNoContent();
    $this->deleteJson('/showroom/attributes/tags/options/sale')->assertNoContent();
    $this->deleteJson('/showroom/attributes/color/options/red')->assertNoContent();

    $this->getJson(productApiPath('TEE-1'))
        ->assertJsonMissingPath('data.values.fabric')
        ->assertJsonMissingPath('data.values.color')
        ->assertJsonPath('data.values.tags.0.data', ['summer']);
});
