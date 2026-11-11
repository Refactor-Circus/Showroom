<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    Catalog::apparel();

    $this->postJson('/showroom/attributes', ['code' => 'copy', 'type' => 'textarea', 'is_localizable' => true, 'is_scopable' => true])->assertCreated();

    // Name everywhere; copy only for the web channel.
    $this->patchJson('/showroom/families/shirts', ['attributes' => [
        ['attribute' => 'name', 'is_required' => true],
        ['attribute' => 'copy', 'is_required' => true, 'required_channels' => ['ecommerce']],
        ...variantAttributes(),
    ]])->assertOk()->assertJsonPath('data.attributes.1.required_channels', ['ecommerce']);
});

/**
 * The attributes the catalog's family variants place, which must stay in
 * the family, not required.
 *
 * @return array<int, array{attribute: string}>
 */
function variantAttributes(?string $except = null): array
{
    return array_values(array_map(
        fn (string $code): array => ['attribute' => $code],
        array_filter(['color', 'size', 'ean', 'price', 'weight'], fn (string $code): bool => $code !== $except),
    ));
}

/**
 * The API path of a product, addressed by its `id` as routes bind it.
 */
function completenessProductPath(string $identifier): string
{
    return '/showroom/products/'.ProductModel::query()->where('identifier', $identifier)->value('id');
}

/**
 * @return array<string, int>
 */
function ratios(string $identifier): array
{
    return collect(test()->getJson(completenessProductPath($identifier))->assertOk()->json('data.completeness'))
        ->mapWithKeys(fn (array $score): array => [$score['scope'].'/'.$score['locale'] => $score['ratio']])
        ->all();
}

it('scores each channel and locale', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'TEE', 'family' => 'shirts', 'values' => [
        'copy' => [['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Web copy']],
    ]])->assertCreated();

    expect(ratios('TEE'))->toBe(['ecommerce/en' => 50, 'ecommerce/fr' => 0, 'print/en' => 0]);

    $this->getJson(completenessProductPath('TEE'))->assertJsonPath('data.completeness.1.missing_attributes', ['name', 'copy']);

    $this->patchJson(completenessProductPath('TEE'), ['values' => ['name' => Catalog::value('Classic tee')]])->assertOk();

    expect(ratios('TEE'))->toBe(['ecommerce/en' => 100, 'ecommerce/fr' => 50, 'print/en' => 100]);
});

it('counts inherited values and follows family and channel changes', function (): void {
    $this->postJson('/showroom/product-models', ['code' => 'polo', 'family_variant' => 'shirts_by_size', 'values' => ['name' => Catalog::value('Polo')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'POLO-M', 'parent' => 'polo', 'values' => ['size' => Catalog::value('m')]])->assertCreated();

    expect(ratios('POLO-M')['print/en'])->toBe(100);

    // A new requirement lowers every product of the family.
    $this->patchJson('/showroom/families/shirts', ['attributes' => [
        ['attribute' => 'name', 'is_required' => true],
        ['attribute' => 'copy', 'is_required' => true, 'required_channels' => ['ecommerce']],
        ['attribute' => 'color', 'is_required' => true],
        ...variantAttributes(except: 'color'),
    ]])->assertOk();

    expect(ratios('POLO-M')['print/en'])->toBe(50);

    // A new channel scores every product.
    $this->postJson('/showroom/channels', ['code' => 'wholesale', 'locales' => ['de']])->assertCreated();

    expect(ratios('POLO-M'))->toHaveKey('wholesale/de');
});

it('counts only the family\'s required attributes, whatever else the product holds', function (): void {
    $this->postJson('/showroom/attributes', ['code' => 'fabric', 'type' => 'text'])->assertCreated();

    $this->postJson('/showroom/products', ['identifier' => 'TEE', 'family' => 'shirts', 'values' => [
        'copy' => [['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Web copy']],
    ]])->assertCreated();

    $before = ratios('TEE');

    // A value outside the family counts neither towards nor against completeness.
    $this->patchJson(completenessProductPath('TEE'), ['values' => ['fabric' => Catalog::value('Cotton')]])
        ->assertOk()
        ->assertJsonPath('data.values.fabric.0.data', 'Cotton')
        ->assertJsonPath('data.completeness.1.missing_attributes', ['name', 'copy']);

    expect(ratios('TEE'))->toBe($before)->toBe(['ecommerce/en' => 50, 'ecommerce/fr' => 0, 'print/en' => 0]);
});

it('has no completeness without a family', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'LOOSE'])->assertCreated()->assertJsonPath('data.completeness', []);
});

it('finds products by completeness', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'DONE', 'family' => 'shirts', 'values' => [
        'name' => Catalog::value('Done'),
        'copy' => [
            ['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Copy'],
            ['locale' => 'fr', 'scope' => 'ecommerce', 'data' => 'Texte'],
        ],
    ]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'HALF', 'family' => 'shirts', 'values' => [
        'name' => Catalog::value('Half'),
        'copy' => [['locale' => 'en', 'scope' => 'ecommerce', 'data' => 'Copy']],
    ]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'LOOSE'])->assertCreated();

    $identifiers = fn (array $complete): array => collect($this->getJson('/showroom/products?'.http_build_query(['complete' => $complete]))->assertOk()->json('data'))->pluck('identifier')->all();

    expect($identifiers(['scope' => 'ecommerce']))->toBe(['DONE'])
        ->and($identifiers(['scope' => 'ecommerce', 'locale' => 'en']))->toBe(['DONE', 'HALF'])
        ->and($identifiers(['scope' => 'ecommerce', 'min' => 50]))->toBe(['DONE', 'HALF'])
        ->and($identifiers(['scope' => 'print']))->toBe(['DONE', 'HALF']);
});
