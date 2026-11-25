<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use Workbench\App\Models\User;

/**
 * Refuse `viewAny` on products, and only that, for every user.
 */
function refuseListingProducts(): void
{
    Gate::before(fn (mixed $user, string $ability, array $arguments): ?bool => $ability === 'viewAny' && ($arguments[0] ?? null) === ProductModel::class ? false : null);
}

it('answers the catalog figures', function (): void {
    Catalog::apparel();

    $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE', 'family' => 'shirts', 'values' => ['name' => Catalog::value('Tee')]])->assertCreated();
    $id = ProductModel::query()->where('identifier', 'TEE')->value('id');
    $this->postJson(route('showroom.products.transitions.store', $id), ['transition' => 'submit'])->assertOk();

    $this->getJson(route('showroom.stats.show'))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'status' => ['counts' => ['draft', 'in_review', 'approved', 'archived'], 'published'],
            'completeness' => [['channel', 'locale', 'products', 'complete', 'average']],
            'review_queue' => ['products' => [['id', 'identifier', 'slug', 'status', 'updated_at']], 'total'],
            'recent_versions' => [['version', 'action', 'comment', 'author', 'changes', 'created_at', 'subject' => ['type', 'id', 'key']]],
        ]])
        ->assertJsonPath('data.status.counts', ['draft' => 0, 'in_review' => 1, 'approved' => 0, 'archived' => 0])
        ->assertJsonPath('data.status.published', 0)
        ->assertJsonPath('data.completeness.0.channel', 'ecommerce')
        ->assertJsonPath('data.completeness.0.locale', 'en')
        ->assertJsonPath('data.review_queue.total', 1)
        ->assertJsonPath('data.review_queue.products.0.identifier', 'TEE')
        // Created, then submitted.
        ->assertJsonCount(2, 'data.recent_versions')
        ->assertJsonPath('data.recent_versions.0.subject', ['type' => 'product', 'id' => $id, 'key' => 'TEE'])
        ->assertJsonPath('data.recent_versions.1.subject', ['type' => 'product', 'id' => $id, 'key' => 'TEE']);
});

it('refuses a guest, and a user who may not list products, while authorization is on', function (): void {
    config()->set('showroom.authorization', true);
    $ann = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);

    $this->getJson(route('showroom.stats.show'))->assertUnauthorized();

    $this->actingAs($ann)->getJson(route('showroom.stats.show'))->assertOk();

    refuseListingProducts();

    $this->actingAs($ann)->getJson(route('showroom.stats.show'))->assertForbidden();
});

it('answers anyone while authorization is off', function (): void {
    config()->set('showroom.authorization', false);
    refuseListingProducts();

    $this->getJson(route('showroom.stats.show'))
        ->assertOk()
        ->assertJsonPath('data.status.counts.archived', 0)
        ->assertJsonPath('data.completeness', [])
        ->assertJsonPath('data.review_queue', ['products' => [], 'total' => 0])
        ->assertJsonPath('data.recent_versions', []);
});
