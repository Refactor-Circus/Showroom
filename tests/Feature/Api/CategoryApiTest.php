<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

function category(string $code, ?string $parent = null, int $sort = 0): void
{
    test()->postJson('/showroom/categories', ['code' => $code, 'parent' => $parent, 'sort_order' => $sort])->assertCreated();
}

/**
 * The API path of a product, addressed by its `id` as routes bind it.
 */
function categoryProductPath(string $identifier): string
{
    return '/showroom/products/'.ProductModel::query()->where('identifier', $identifier)->value('id');
}

beforeEach(function (): void {
    // Two trees: master → clothing → (shirts, pants); web → sale.
    category('master');
    category('clothing', 'master');
    category('shirts', 'clothing', 2);
    category('pants', 'clothing', 1);
    category('web');
    category('sale', 'web');
});

it('keeps several independent trees', function (): void {
    expect(collect($this->getJson('/showroom/categories?roots=1')->json('data'))->pluck('code')->all())->toBe(['master', 'web']);

    $this->getJson('/showroom/categories/shirts')
        ->assertOk()
        ->assertJsonPath('data.parent', 'clothing')
        ->assertJsonPath('data.depth', 2)
        ->assertJsonPath('data.chain', ['master', 'clothing', 'shirts']);

    // Siblings in sort order.
    expect(collect($this->getJson('/showroom/categories?parent=clothing')->json('data'))->pluck('code')->all())->toBe(['pants', 'shirts'])
        ->and(collect($this->getJson('/showroom/categories?under=master')->json('data'))->pluck('code')->all())->toBe(['clothing', 'pants', 'shirts']);
});

it('moves a branch within a tree, across trees and to the root', function (): void {
    $this->patchJson('/showroom/categories/clothing', ['parent' => 'sale'])->assertOk();

    $this->getJson('/showroom/categories/shirts')->assertJsonPath('data.chain', ['web', 'sale', 'clothing', 'shirts'])->assertJsonPath('data.depth', 3);

    $this->patchJson('/showroom/categories/clothing', ['parent' => null])->assertOk();

    $this->getJson('/showroom/categories/shirts')->assertJsonPath('data.chain', ['clothing', 'shirts'])->assertJsonPath('data.depth', 1);
    expect(CategoryModel::query()->where('code', 'clothing')->firstOrFail()->tree()->code)->toBe('clothing');
});

it('refuses cycles and code changes', function (): void {
    $this->patchJson('/showroom/categories/clothing', ['parent' => 'shirts'])->assertUnprocessable()->assertJsonValidationErrors('parent');
    $this->patchJson('/showroom/categories/clothing', ['code' => 'apparel'])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('files products and models in categories, and variants inherit them', function (): void {
    Catalog::apparel();

    $this->postJson('/showroom/products', ['identifier' => 'TEE-1', 'categories' => ['shirts', 'sale']])
        ->assertCreated()
        ->assertJsonPath('data.categories', ['sale', 'shirts']);

    $this->postJson('/showroom/products', ['identifier' => 'X', 'categories' => ['nope']])
        ->assertUnprocessable()->assertJsonValidationErrors('categories.0');

    $this->postJson('/showroom/product-models', ['code' => 'polo', 'family_variant' => 'shirts_by_size', 'categories' => ['shirts']])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'POLO-M', 'parent' => 'polo', 'categories' => ['sale'], 'values' => ['size' => Catalog::value('m')]])
        ->assertCreated()
        ->assertJsonPath('data.categories', ['sale', 'shirts']);

    // Replaced, not appended.
    $this->patchJson(categoryProductPath('TEE-1'), ['categories' => ['pants']])->assertOk()->assertJsonPath('data.categories', ['pants']);
    $this->patchJson(categoryProductPath('TEE-1'), ['enabled' => false])->assertOk()->assertJsonPath('data.categories', ['pants']);
});

it('finds products in a category and everything beneath it', function (): void {
    Catalog::apparel();

    $this->postJson('/showroom/products', ['identifier' => 'SHIRT', 'categories' => ['shirts']])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'PANTS', 'categories' => ['pants']])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'NONE'])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'polo', 'family_variant' => 'shirts_by_color_size', 'categories' => ['sale']])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'polo-red', 'parent' => 'polo', 'values' => ['color' => Catalog::value('red')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'POLO-RED-M', 'parent' => 'polo-red', 'values' => ['size' => Catalog::value('m')]])->assertCreated();

    $identifiers = fn (string $category): array => collect($this->getJson('/showroom/products?category='.$category)->json('data'))->pluck('identifier')->all();

    expect($identifiers('clothing'))->toBe(['PANTS', 'SHIRT'])
        ->and($identifiers('master'))->toBe(['PANTS', 'SHIRT'])
        ->and($identifiers('shirts'))->toBe(['SHIRT'])
        ->and($identifiers('web'))->toBe(['POLO-RED-M'])
        ->and($identifiers('missing'))->toBe([]);
});

it('deletes leaves only, unassigning their products', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'SHIRT', 'categories' => ['shirts', 'sale']])->assertCreated();

    $this->deleteJson('/showroom/categories/clothing')
        ->assertConflict()
        ->assertJsonPath('message', 'Category "clothing" still has 2 child categories. Move or delete them first.');

    $this->deleteJson('/showroom/categories/shirts')->assertNoContent();

    $this->getJson(categoryProductPath('SHIRT'))->assertJsonPath('data.categories', ['sale']);
});
