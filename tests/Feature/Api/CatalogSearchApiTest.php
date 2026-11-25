<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use Workbench\App\Models\User;

/**
 * Let every user list only products.
 */
function allowListingOnlyProducts(): void
{
    Gate::before(fn (mixed $user, string $ability, array $arguments): ?bool => $ability === 'viewAny' && ($arguments[0] ?? null) !== ProductModel::class ? false : null);
}

/**
 * A product, a category and an attribute matching "oak".
 */
function seedSearchApiCatalog(): ProductModel
{
    CategoryModel::factory()->create(['code' => 'oak_furniture', 'labels' => ['en' => 'Oak furniture']]);
    AttributeModel::factory()->create(['code' => 'oak_finish', 'labels' => ['en' => 'Oak finish']]);

    return ProductModel::factory()->create(['identifier' => 'OAK-1']);
}

it('answers the hits of every type as {type, id, code, label, subtitle}', function (): void {
    $product = seedSearchApiCatalog();

    $this->getJson(route('showroom.search.index', ['q' => 'oak']))
        ->assertOk()
        ->assertJsonStructure(['data' => [['type', 'id', 'code', 'label', 'subtitle']]])
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0', ['type' => 'products', 'id' => $product->id, 'code' => 'OAK-1', 'label' => 'OAK-1', 'subtitle' => null])
        ->assertJsonPath('data.1.type', 'categories')
        ->assertJsonPath('data.2.type', 'attributes');
});

it('limits the search to one type, and the hits per type', function (): void {
    seedSearchApiCatalog();
    ProductModel::factory()->create(['identifier' => 'OAK-2']);

    $this->getJson(route('showroom.search.index', ['q' => 'oak', 'type' => 'categories']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'oak_furniture');

    $this->getJson(route('showroom.search.index', ['q' => 'oak', 'limit' => 1]))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.code', 'OAK-1');

    // An empty type searches every type.
    $this->getJson(route('showroom.search.index', ['q' => 'oak', 'type' => '']))
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('refuses a missing q, an unknown type and a limit out of range', function (array $query, string $field): void {
    $this->getJson(route('showroom.search.index', $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'no q' => [[], 'q'],
    'a q of 192 characters' => [['q' => str_repeat('a', 192)], 'q'],
    'an unknown type' => [['q' => 'oak', 'type' => 'transfers'], 'type'],
    'a limit of 0' => [['q' => 'oak', 'limit' => 0], 'limit'],
    'a limit of 21' => [['q' => 'oak', 'limit' => 21], 'limit'],
]);

it('refuses a guest while authorization is on', function (): void {
    config()->set('showroom.authorization', true);

    $this->getJson(route('showroom.search.index', ['q' => 'oak']))->assertUnauthorized();
});

it('refuses a user who may list none of the requested types', function (): void {
    config()->set('showroom.authorization', true);
    $ann = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);

    Gate::before(fn (mixed $user, string $ability): ?bool => $ability === 'viewAny' ? false : null);

    $this->actingAs($ann)->getJson(route('showroom.search.index', ['q' => 'oak']))->assertForbidden();
});

it('refuses a type the user may not list', function (): void {
    config()->set('showroom.authorization', true);
    seedSearchApiCatalog();
    $ann = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);

    allowListingOnlyProducts();

    $this->actingAs($ann)->getJson(route('showroom.search.index', ['q' => 'oak', 'type' => 'categories']))->assertForbidden();
    $this->actingAs($ann)->getJson(route('showroom.search.index', ['q' => 'oak', 'type' => 'products']))->assertOk()->assertJsonCount(1, 'data');
});

it('answers a user allowed only products with product hits alone', function (): void {
    config()->set('showroom.authorization', true);
    seedSearchApiCatalog();
    $ann = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);

    allowListingOnlyProducts();

    $this->actingAs($ann)->getJson(route('showroom.search.index', ['q' => 'oak']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'products');
});

it('searches every type for anyone while authorization is off', function (): void {
    config()->set('showroom.authorization', false);
    seedSearchApiCatalog();

    Gate::before(fn (mixed $user, string $ability): ?bool => $ability === 'viewAny' ? false : null);

    $this->getJson(route('showroom.search.index', ['q' => 'oak']))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
