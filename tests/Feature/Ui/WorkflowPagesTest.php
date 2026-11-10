<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    Catalog::apparel();
    $this->tee = $this->postJson('/showroom/products', ['identifier' => 'TEE', 'family' => 'shirts', 'values' => ['name' => Catalog::value('Tee')]])->assertCreated()->json('data.id');
});

it('shows workflow, completeness and history on the product page', function (): void {
    $this->get(route('atrium.showroom.products.show', $this->tee))
        ->assertOk()
        ->assertSee('data-testid="workflow"', false)
        ->assertSee('data-testid="transition-submit"', false)
        ->assertDontSee('data-testid="transition-publish"', false)
        ->assertSee('data-completeness="ecommerce/en"', false)
        ->assertSee('data-testid="history"', false);
});

it('moves a product through the workflow from its page', function (): void {
    $this->post(route('atrium.showroom.products.transition', $this->tee), ['transition' => 'submit'])->assertSessionHasNoErrors();
    $this->post(route('atrium.showroom.products.transition', $this->tee), ['transition' => 'approve'])->assertSessionHasNoErrors();
    $this->post(route('atrium.showroom.products.transition', $this->tee), ['transition' => 'publish'])->assertSessionHasNoErrors();

    $product = ProductModel::query()->firstOrFail();

    expect($product->published_version)->not->toBeNull();

    $this->from(route('atrium.showroom.products.show', $this->tee))
        ->post(route('atrium.showroom.products.transition', $this->tee), ['transition' => 'approve'])
        ->assertSessionHasErrors('transition');

    $this->get(route('atrium.showroom.products.index', ['status' => 'approved']))->assertOk()->assertSee('TEE')->assertSee('v'.$product->published_version);
});

it('reverts from the history', function (): void {
    $this->patchJson('/showroom/products/'.$this->tee, ['values' => ['name' => Catalog::value('Renamed')]])->assertOk();

    $this->get(route('atrium.showroom.products.show', $this->tee))->assertOk()->assertSee('data-testid="revert-1"', false);

    $this->post(route('atrium.showroom.products.revert', $this->tee), ['version' => 1])->assertSessionHasNoErrors();

    expect(ProductModel::query()->firstOrFail()->value('name'))->toBe('Tee');
});

it('narrows a requirement to channels from the family page', function (): void {
    $family = FamilyModel::query()->with('familyAttributes')->where('code', 'shirts')->firstOrFail();

    $rows = $family->familyAttributes->values()->map(fn ($attribute, int $index): array => [
        'attribute' => $attribute->code,
        'is_required' => $attribute->code === 'name' ? '1' : '0',
        'sort_order' => (string) $index,
        'required_channels' => $attribute->code === 'name' ? 'print' : '',
    ])->all();

    $this->patch(route('atrium.showroom.families.update', 'shirts'), ['attributes' => $rows, 'label_attribute' => 'name'])->assertSessionHasNoErrors();

    $this->getJson('/showroom/families/shirts')->assertJsonPath('data.attributes.0.required_channels', ['print']);
});
