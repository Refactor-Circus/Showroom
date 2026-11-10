<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');
});

it('manages locales and channels on one page', function (): void {
    $this->get(route('atrium.showroom.channels.index'))->assertOk()->assertSee(__('showroom::showroom.no_channels'));

    $this->post(route('atrium.showroom.locales.store'), ['code' => 'en_US'])->assertRedirect(route('atrium.showroom.channels.index'));
    $this->post(route('atrium.showroom.locales.store'), ['code' => 'fr_FR'])->assertRedirect();

    $this->post(route('atrium.showroom.channels.store'), ['code' => 'web', 'locales' => ['en_US', 'fr_FR'], 'currencies' => 'usd, eur'])
        ->assertRedirect(route('atrium.showroom.channels.show', 'web'));

    $channel = ChannelModel::query()->with('locales')->firstOrFail();

    expect($channel->currencies)->toBe(['USD', 'EUR'])
        ->and($channel->locales->pluck('code')->all())->toBe(['en_US', 'fr_FR']);

    $this->get(route('atrium.showroom.channels.index'))->assertOk()->assertSee('data-locale="en_US"', false)->assertSee('web');

    $this->patch(route('atrium.showroom.channels.update', 'web'), ['locales' => ['en_US'], 'currencies' => 'USD', 'category_tree' => ''])->assertSessionHasNoErrors();

    $this->from(route('atrium.showroom.channels.index'))
        ->delete(route('atrium.showroom.locales.destroy', 'en_US'))
        ->assertSessionHasErrors('locale');

    $this->delete(route('atrium.showroom.locales.destroy', 'fr_FR'))->assertRedirect();
    expect(LocaleModel::query()->pluck('code')->all())->toBe(['en_US']);

    $this->delete(route('atrium.showroom.channels.destroy', 'web'))->assertRedirect(route('atrium.showroom.channels.index'));
});

it('edits values per locale and channel', function (): void {
    Catalog::apparel();
    $this->postJson('/showroom/attributes', ['code' => 'copy', 'type' => 'text', 'is_localizable' => true, 'is_scopable' => true]);
    $tee = ProductModel::factory()->create(['identifier' => 'TEE-1']);

    $this->get(route('atrium.showroom.products.show', ['product' => $tee, 'add' => 'copy', 'locale' => 'fr', 'channel' => 'ecommerce']))
        ->assertOk()
        ->assertSee('data-testid="slot-picker"', false)
        ->assertSee('data-attribute="copy"', false);

    $this->patch(route('atrium.showroom.products.update', $tee), ['add' => 'copy', 'locale' => 'fr', 'channel' => 'ecommerce', 'v' => ['copy' => 'Texte web']])
        ->assertRedirect(route('atrium.showroom.products.show', ['product' => $tee, 'locale' => 'fr', 'channel' => 'ecommerce']));

    $this->patch(route('atrium.showroom.products.update', $tee), ['add' => 'copy', 'locale' => 'en', 'channel' => 'print', 'v' => ['copy' => 'Print copy']]);

    $product = ProductModel::query()->firstOrFail();

    expect($product->value('copy', 'ecommerce', 'fr'))->toBe('Texte web')
        ->and($product->value('copy', 'print', 'en'))->toBe('Print copy');

    $this->get(route('atrium.showroom.products.show', ['product' => $tee, 'locale' => 'fr', 'channel' => 'ecommerce']))->assertOk()->assertSee('Texte web');
});
