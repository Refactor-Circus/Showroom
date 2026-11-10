<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');
});

it('manages association types', function (): void {
    $this->get(route('atrium.showroom.association-types.index'))->assertOk()->assertSee(__('showroom::showroom.no_association_types'));

    $this->post(route('atrium.showroom.association-types.store'), ['code' => 'bundle', 'is_two_way' => '0', 'is_quantified' => '1'])
        ->assertRedirect(route('atrium.showroom.association-types.index'));

    expect(AssociationTypeModel::query()->firstOrFail()->is_quantified)->toBeTrue();

    $this->get(route('atrium.showroom.association-types.index'))->assertOk()->assertSee('bundle');

    $this->delete(route('atrium.showroom.association-types.destroy', 'bundle'))->assertRedirect();
    expect(AssociationTypeModel::query()->count())->toBe(0);
});

it('adds and removes associations one at a time from a product page', function (): void {
    AssociationTypeModel::factory()->create(['code' => 'cross_sell']);
    AssociationTypeModel::factory()->create(['code' => 'bundle', 'is_quantified' => true]);

    $products = [];

    foreach (['TEE', 'CAP', 'SOCKS'] as $identifier) {
        $products[$identifier] = ProductModel::factory()->create(['identifier' => $identifier]);
    }

    $add = fn (string $type, string $target, ?int $quantity = null) => $this->post(route('atrium.showroom.associations.add'), array_filter([
        'source_kind' => 'product',
        'source' => 'TEE',
        'type' => $type,
        'target_kind' => 'products',
        'target' => $target,
        'quantity' => $quantity,
    ]))->assertSessionHasNoErrors();

    $add('cross_sell', 'CAP');
    $add('cross_sell', 'SOCKS');
    $add('bundle', 'SOCKS', 3);

    $this->get(route('atrium.showroom.products.show', $products['TEE']))
        ->assertOk()
        ->assertSee('data-association-type="cross_sell"', false)
        ->assertSee('× 3');

    $this->delete(route('atrium.showroom.associations.remove'), [
        'source_kind' => 'product', 'source' => 'TEE', 'type' => 'cross_sell', 'target_kind' => 'products', 'target' => 'CAP',
    ])->assertSessionHasNoErrors();

    $this->getJson('/showroom/products/'.$products['TEE']->id)
        ->assertJsonPath('data.associations.cross_sell.products', ['SOCKS'])
        ->assertJsonPath('data.quantified_associations.bundle.products', [['identifier' => 'SOCKS', 'quantity' => 3]]);

    $this->post(route('atrium.showroom.associations.add'), [
        'source_kind' => 'product', 'source' => 'TEE', 'type' => 'cross_sell', 'target_kind' => 'products', 'target' => 'NOPE',
    ])->assertSessionHasErrors('associations.cross_sell.products.1');
});
