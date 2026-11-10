<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    Catalog::apparel();

    // A value per channel and locale: the web copy differs from the print copy.
    $this->postJson('/showroom/attributes', ['code' => 'copy', 'type' => 'textarea', 'is_localizable' => true, 'is_scopable' => true])->assertCreated();
    $this->postJson('/showroom/attributes', ['code' => 'list_price', 'type' => 'price', 'is_scopable' => true])->assertCreated();
});

/**
 * The API path of a product, addressed by its `id` as routes bind it.
 */
function channelProductPath(string $identifier): string
{
    return '/showroom/products/'.ProductModel::query()->where('identifier', $identifier)->value('id');
}

it('manages locales and channels', function (): void {
    $this->getJson('/showroom/locales')->assertOk()->assertJsonCount(3, 'data');

    $this->getJson('/showroom/channels/ecommerce')
        ->assertOk()
        ->assertJsonPath('data.locales', ['en', 'fr'])
        ->assertJsonPath('data.currencies', ['USD', 'EUR'])
        ->assertJsonPath('data.category_tree', null);

    $this->postJson('/showroom/categories', ['code' => 'master'])->assertCreated();
    $this->postJson('/showroom/categories', ['code' => 'shirts', 'parent' => 'master'])->assertCreated();

    $this->patchJson('/showroom/channels/print', ['category_tree' => 'master', 'locales' => ['en', 'de']])
        ->assertOk()
        ->assertJsonPath('data.category_tree', 'master')
        ->assertJsonPath('data.locales', ['de', 'en']);

    $this->patchJson('/showroom/channels/print', ['category_tree' => 'shirts'])
        ->assertUnprocessable()->assertJsonValidationErrors(['category_tree' => 'Category "shirts" is not the root of a tree.']);

    $this->patchJson('/showroom/channels/print', ['locales' => []])->assertUnprocessable()->assertJsonValidationErrors('locales');
    $this->postJson('/showroom/channels', ['code' => 'bad', 'locales' => ['xx']])->assertUnprocessable()->assertJsonValidationErrors('locales.0');
    $this->patchJson('/showroom/channels/print', ['code' => 'paper'])->assertUnprocessable()->assertJsonValidationErrors('code');
    $this->patchJson('/showroom/locales/en', ['code' => 'en_GB'])->assertUnprocessable()->assertJsonValidationErrors('code');

    // A channel's tree cannot be deleted from under it.
    $this->deleteJson('/showroom/categories/shirts')->assertNoContent();
    $this->deleteJson('/showroom/categories/master')
        ->assertConflict()
        ->assertJsonPath('message', 'Category "master" is the category tree of channel "print". Choose another tree for them first.');
});

it('holds values to existing locales and channels', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-1',
        'values' => [
            'description' => Catalog::value('Hola', 'es'),
            'copy' => Catalog::value('Web copy', 'en', 'marketplace'),
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors(['values.description.0.locale', 'values.copy.0.scope']);
});

it('holds scoped values to the channel\'s own locales and currencies', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-1',
        'values' => [
            'copy' => Catalog::value('Druck', 'de', 'print'),
            'list_price' => Catalog::value([['amount' => 10, 'currency' => 'EUR']], null, 'print'),
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'values.copy.0.locale' => 'Channel "print" does not publish in locale "de". Its locales: en.',
            'values.list_price.0.data.0.currency' => 'Channel "print" does not sell in EUR. Its currencies: USD.',
        ]);

    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-1',
        'values' => [
            'copy' => [
                ['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Web copy'],
                ['locale' => 'fr', 'scope' => 'ecommerce', 'data' => 'Texte web'],
                ['locale' => 'en', 'scope' => 'print', 'data' => 'Print copy'],
            ],
            'list_price' => Catalog::value([['amount' => 10, 'currency' => 'EUR']], null, 'ecommerce'),
        ],
    ])->assertCreated()->assertJsonCount(3, 'data.values.copy');
});

it('reads one channel\'s and some locales\' values', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-1',
        'values' => [
            'name' => Catalog::value('Classic tee'),
            'description' => [
                ['locale' => 'en', 'scope' => null, 'data' => 'Soft'],
                ['locale' => 'fr', 'scope' => null, 'data' => 'Doux'],
            ],
            'copy' => [
                ['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Web copy'],
                ['locale' => 'fr', 'scope' => 'ecommerce', 'data' => 'Texte web'],
                ['locale' => 'en', 'scope' => 'print', 'data' => 'Print copy'],
            ],
        ],
    ])->assertCreated();

    $values = $this->getJson(channelProductPath('TEE-1').'?'.http_build_query(['scope' => 'print', 'locales' => ['en']]))->assertOk()->json('data.values');

    expect(array_keys($values))->toBe(['copy', 'description', 'name'])
        ->and($values['copy'])->toBe([['locale' => 'en', 'scope' => 'print', 'data' => 'Print copy']])
        ->and($values['description'])->toBe([['locale' => 'en', 'scope' => null, 'data' => 'Soft']]);

    $listed = $this->getJson('/showroom/products?'.http_build_query(['scope' => 'ecommerce', 'locales' => ['fr']]))->assertOk()->json('data.0.values');

    expect($listed['copy'])->toBe([['locale' => 'fr', 'scope' => 'ecommerce', 'data' => 'Texte web']]);

    $this->getJson(channelProductPath('TEE-1').'?scope=nowhere')->assertUnprocessable()->assertJsonValidationErrors('scope');
});

it('purges the values of a deleted channel or locale', function (): void {
    $this->postJson('/showroom/products', [
        'identifier' => 'TEE-1',
        'values' => [
            'description' => [
                ['locale' => 'en', 'scope' => null, 'data' => 'Soft'],
                ['locale' => 'fr', 'scope' => null, 'data' => 'Doux'],
            ],
            'copy' => [
                ['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Web copy'],
                ['locale' => 'en', 'scope' => 'print', 'data' => 'Print copy'],
            ],
        ],
    ])->assertCreated();

    $this->deleteJson('/showroom/locales/fr')
        ->assertConflict()
        ->assertJsonPath('message', 'Locale "fr" is used by channel "ecommerce". Remove it from them first.');

    $this->deleteJson('/showroom/channels/print')->assertNoContent();
    $this->patchJson('/showroom/channels/ecommerce', ['locales' => ['en']])->assertOk();
    $this->deleteJson('/showroom/locales/fr')->assertNoContent();

    $values = $this->getJson(channelProductPath('TEE-1'))->json('data.values');

    expect($values['copy'])->toBe([['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Web copy']])
        ->and($values['description'])->toBe([['locale' => 'en', 'scope' => null, 'data' => 'Soft']]);
});
