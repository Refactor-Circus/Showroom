<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');
});

it('uploads, browses and edits assets', function (): void {
    $this->get(route('atrium.showroom.assets.index'))->assertOk()->assertSee(__('showroom::showroom.no_assets'));

    $this->post(route('atrium.showroom.assets.store'), ['code' => 'hero', 'file' => UploadedFile::fake()->image('hero.jpg'), 'labels' => ['en' => 'Hero shot']])
        ->assertRedirect(route('atrium.showroom.assets.show', 'hero'));

    $this->get(route('atrium.showroom.assets.index'))->assertOk()->assertSee('data-asset="hero"', false);
    $this->get(route('atrium.showroom.assets.show', 'hero'))->assertOk()->assertSee('Hero shot')->assertSee(__('showroom::showroom.no_links'));

    $this->patch(route('atrium.showroom.assets.update', 'hero'), ['labels' => ['en' => 'Hero'], 'file' => UploadedFile::fake()->image('hero2.png')])
        ->assertSessionHasNoErrors();

    expect(AssetModel::query()->firstOrFail()->filename)->toBe('hero2.png');

    $this->delete(route('atrium.showroom.assets.destroy', 'hero'))->assertRedirect(route('atrium.showroom.assets.index'));
});

it('links and unlinks an asset from its page', function (): void {
    $tee = ProductModel::factory()->create(['identifier' => 'TEE-1']);
    $this->post(route('atrium.showroom.assets.store'), ['code' => 'hero', 'file' => UploadedFile::fake()->image('hero.jpg')]);

    $this->post(route('atrium.showroom.assets.attach', 'hero'), ['type' => 'product', 'target' => 'TEE-1', 'role' => 'image'])->assertSessionHasNoErrors();

    $this->get(route('atrium.showroom.assets.show', 'hero'))->assertOk()->assertSee('TEE-1');
    $this->get(route('atrium.showroom.products.show', $tee))->assertOk()->assertSee('data-asset="hero"', false);

    $this->delete(route('atrium.showroom.assets.detach', 'hero'), ['type' => 'product', 'target' => 'TEE-1', 'role' => 'image'])->assertSessionHasNoErrors();

    expect(ProductModel::query()->firstOrFail()->assets)->toHaveCount(0);

    $this->post(route('atrium.showroom.assets.attach', 'hero'), ['type' => 'product', 'target' => 'NOPE'])->assertSessionHasErrors('target');
});

it('uploads and links a file from a product page in one step', function (): void {
    $tee = ProductModel::factory()->create(['identifier' => 'TEE-1']);

    $this->from(route('atrium.showroom.products.show', $tee))
        ->post(route('atrium.showroom.assets.upload'), ['type' => 'product', 'target' => 'TEE-1', 'role' => 'image', 'file' => UploadedFile::fake()->image('front.jpg')])
        ->assertRedirect(route('atrium.showroom.products.show', $tee))
        ->assertSessionHasNoErrors();

    $product = ProductModel::query()->firstOrFail();

    expect($product->assets)->toHaveCount(1)
        ->and($product->assets->first()?->getRelation('pivot')->getAttribute('role'))->toBe('image');

    // A bad target leaves no stray asset behind.
    $this->post(route('atrium.showroom.assets.upload'), ['type' => 'product', 'target' => 'NOPE', 'file' => UploadedFile::fake()->image('x.jpg')])
        ->assertSessionHasErrors('target');

    expect(AssetModel::query()->count())->toBe(1);
});
