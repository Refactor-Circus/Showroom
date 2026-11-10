<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');
});

it('builds and browses a category tree', function (): void {
    $this->get(route('atrium.showroom.categories.index'))->assertOk()->assertSee(__('showroom::showroom.no_trees'));

    $this->post(route('atrium.showroom.categories.store'), ['code' => 'master', 'labels' => ['en' => 'Master catalog']])
        ->assertRedirect(route('atrium.showroom.categories.show', 'master'));

    $this->post(route('atrium.showroom.categories.store'), ['code' => 'clothing', 'parent' => 'master'])
        ->assertRedirect(route('atrium.showroom.categories.show', 'master'));
    $this->post(route('atrium.showroom.categories.store'), ['code' => 'shirts', 'parent' => 'clothing', 'labels' => ['en' => 'Shirts']]);

    $this->get(route('atrium.showroom.categories.index'))->assertOk()->assertSee('Master catalog');

    // The whole branch renders nested beneath the tree root.
    $this->get(route('atrium.showroom.categories.show', 'master'))->assertOk()->assertSeeInOrder(['clothing', 'Shirts']);

    $this->get(route('atrium.showroom.categories.show', 'shirts'))
        ->assertOk()
        ->assertSee('data-testid="category-chain"', false)
        ->assertSeeInOrder(['Master catalog', 'clothing', 'Shirts']);

    $this->patch(route('atrium.showroom.categories.update', 'shirts'), ['parent' => 'master'])->assertSessionHasNoErrors();
    expect(CategoryModel::query()->where('code', 'shirts')->firstOrFail()->parent?->code)->toBe('master');

    $this->from(route('atrium.showroom.categories.show', 'master'))
        ->delete(route('atrium.showroom.categories.destroy', 'master'))
        ->assertSessionHasErrors('category');

    $this->delete(route('atrium.showroom.categories.destroy', 'clothing'))->assertRedirect(route('atrium.showroom.categories.show', 'master'));
});

it('files a product in categories from its page', function (): void {
    $this->post(route('atrium.showroom.categories.store'), ['code' => 'master']);
    $this->post(route('atrium.showroom.categories.store'), ['code' => 'shirts', 'parent' => 'master']);
    $this->post(route('atrium.showroom.categories.store'), ['code' => 'sale', 'parent' => 'master']);
    $tee = ProductModel::factory()->create(['identifier' => 'TEE-1']);

    $this->patch(route('atrium.showroom.products.update', $tee), ['categories' => 'shirts, sale , '])->assertSessionHasNoErrors();

    expect(ProductModel::query()->firstOrFail()->categories->pluck('code')->all())->toBe(['sale', 'shirts']);

    $this->get(route('atrium.showroom.products.show', $tee))->assertOk()->assertSee('sale, shirts');
    $this->get(route('atrium.showroom.products.index', ['category' => 'master']))->assertOk()->assertSee('TEE-1');

    $this->patch(route('atrium.showroom.products.update', $tee), ['categories' => 'unknown'])->assertSessionHasErrors('categories.0');
});
