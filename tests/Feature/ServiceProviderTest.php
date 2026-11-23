<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Asset\AssetServiceProvider;
use RefactorCircus\Showroom\Domains\Association\AssociationServiceProvider;
use RefactorCircus\Showroom\Domains\Attribute\AttributeServiceProvider;
use RefactorCircus\Showroom\Domains\Category\CategoryServiceProvider;
use RefactorCircus\Showroom\Domains\Channel\ChannelServiceProvider;
use RefactorCircus\Showroom\Domains\DomainServiceProvider;
use RefactorCircus\Showroom\Domains\Family\FamilyServiceProvider;
use RefactorCircus\Showroom\Domains\Owner\OwnerServiceProvider;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\ProductServiceProvider;
use RefactorCircus\Showroom\Domains\ProductModel\ProductModelServiceProvider;
use RefactorCircus\Showroom\Domains\Search\SearchServiceProvider;
use RefactorCircus\Showroom\Domains\Transfer\TransferServiceProvider;
use RefactorCircus\Showroom\Domains\Workflow\WorkflowServiceProvider;
use RefactorCircus\Showroom\Showroom;
use RefactorCircus\Showroom\ShowroomServiceProvider;

it('resolves the Showroom singleton', function (): void {
    expect(app(Showroom::class))->toBe(app(Showroom::class));
});

it('merges the package config', function (): void {
    expect(config('showroom.routes.prefix'))->toBe('showroom')
        ->and(config('showroom.mcp.web.enabled'))->toBeFalse()
        ->and(config('showroom.pagination.per_page'))->toBe(25);
});

it('registers the API routes', function (): void {
    expect(Route::has('showroom.attributes.index'))->toBeTrue()
        ->and(route('showroom.attributes.index'))->toEndWith('/showroom/attributes');
});

it('serves its audit history, answering 404 while no audit log is installed', function (): void {
    expect(Route::has('showroom.history.index'))->toBeTrue()
        ->and(route('showroom.history.index'))->toEndWith('/showroom/history');

    $this->getJson(route('showroom.history.index'))
        ->assertNotFound()
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'No audit log is installed'));
});

it('registers every domain service provider', function (string $provider): void {
    expect(app()->getProvider($provider))->not->toBeNull();
})->with([
    DomainServiceProvider::class,
    AssetServiceProvider::class,
    AssociationServiceProvider::class,
    AttributeServiceProvider::class,
    CategoryServiceProvider::class,
    ChannelServiceProvider::class,
    FamilyServiceProvider::class,
    OwnerServiceProvider::class,
    ProductServiceProvider::class,
    ProductModelServiceProvider::class,
    SearchServiceProvider::class,
    TransferServiceProvider::class,
    WorkflowServiceProvider::class,
]);

it('keeps every API route name now each domain loads its own routes', function (): void {
    $names = [
        'attribute-groups.index',
        'attribute-groups.store',
        'attribute-groups.show',
        'attribute-groups.update',
        'attribute-groups.destroy',
        'attributes.index',
        'attributes.store',
        'attributes.show',
        'attributes.update',
        'attributes.destroy',
        'attributes.options.index',
        'attributes.options.store',
        'attributes.options.update',
        'attributes.options.destroy',
        'families.index',
        'families.store',
        'families.show',
        'families.update',
        'families.destroy',
        'family-variants.index',
        'family-variants.store',
        'family-variants.show',
        'family-variants.update',
        'family-variants.destroy',
        'product-models.index',
        'product-models.store',
        'product-models.show',
        'product-models.update',
        'product-models.destroy',
        'owner-types.index',
        'owner-types.store',
        'owner-types.show',
        'owner-types.update',
        'owner-types.destroy',
        'owners.index',
        'owners.store',
        'owners.show',
        'owners.update',
        'owners.destroy',
        'categories.index',
        'categories.store',
        'categories.show',
        'categories.update',
        'categories.destroy',
        'locales.index',
        'locales.store',
        'locales.show',
        'locales.update',
        'locales.destroy',
        'channels.index',
        'channels.store',
        'channels.show',
        'channels.update',
        'channels.destroy',
        'association-types.index',
        'association-types.store',
        'association-types.show',
        'association-types.update',
        'association-types.destroy',
        'imports.store',
        'exports.store',
        'assets.index',
        'assets.store',
        'assets.show',
        'assets.update',
        'assets.destroy',
        'assets.links.store',
        'assets.links.destroy',
        'products.index',
        'products.store',
        'products.show',
        'products.update',
        'products.destroy',
        'products.transitions.store',
        'products.versions.index',
        'products.versions.show',
        'products.revert',
    ];

    foreach ($names as $name) {
        expect(Route::has('showroom.'.$name))->toBeTrue('showroom.'.$name);
    }
});

it('loads the package translations', function (): void {
    expect(__('showroom::showroom.attributes'))->toBe('Attributes');
});

it('publishes every resource group under its own tag', function (string $tag): void {
    expect(ServiceProvider::pathsToPublish(ShowroomServiceProvider::class, $tag))->not->toBeEmpty();
})->with(['showroom', 'showroom-config', 'showroom-lang', 'showroom-migrations']);

it('ships no views: no views publish tag, view namespace or Blade directive', function (): void {
    expect(ServiceProvider::publishableGroups())->not->toContain('showroom-views')
        ->and(ServiceProvider::pathsToPublish(ShowroomServiceProvider::class, 'showroom-views'))->toBeEmpty()
        ->and(View::getFinder()->getHints())->not->toHaveKey('showroom')
        ->and(Blade::getCustomDirectives())->not->toHaveKey('showroomCan')
        ->and(Blade::getCustomDirectives())->not->toHaveKey('endshowroomCan');
});

it('gives ProductStatus no badge()', function (): void {
    expect(method_exists(ProductStatus::class, 'badge'))->toBeFalse();
});
