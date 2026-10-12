<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Impex\Domains\Subscription\Actions\CreateSubscriberAction;
use RefactorCircus\Impex\Domains\Subscription\Actions\CreateSubscriptionAction;
use RefactorCircus\Impex\Domains\Subscription\Data\CreateSubscriberData;
use RefactorCircus\Impex\Domains\Subscription\Data\CreateSubscriptionData;
use RefactorCircus\Impex\Domains\Subscription\Models\SubscriptionModel;
use RefactorCircus\Impex\Domains\Subscription\Services\Exporter;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    config()->set('showroom.impex.webhooks.enabled', true);
    config()->set('showroom.workflow.require_approval', false);
    config()->set('impex.outbound.guard', false);
    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');

    // The integration registers at boot; with webhooks switched on after it,
    // register again.
    app(ImpexIntegration::class)->register();

    Http::fake(['vendor.test/*' => Http::response(['ok' => true])]);

    Catalog::apparel();
    $this->postJson('/showroom/categories', ['code' => 'master'])->assertCreated();
    $this->postJson('/showroom/categories', ['code' => 'tools', 'parent' => 'master'])->assertCreated();
    $this->postJson('/showroom/categories', ['code' => 'garden', 'parent' => 'master'])->assertCreated();
});

/**
 * @param  array<string, mixed>  $data
 */
function vendorSubscription(array $data = []): SubscriptionModel
{
    return app(CreateSubscriptionAction::class)->execute(
        app(CreateSubscriberAction::class)->execute(new CreateSubscriberData(name: 'Vendor')),
        CreateSubscriptionData::from(['stream' => 'showroom.products', 'endpoint' => ['url' => 'https://vendor.test/'.($data['path'] ?? 'hooks')], ...array_diff_key($data, ['path' => true])]),
    )['subscription'];
}

/**
 * @param  array<string, mixed>  $values
 * @param  list<string>  $categories
 */
function publishedProduct(string $identifier, array $values, array $categories = ['tools']): void
{
    test()->postJson('/showroom/products', [
        'identifier' => $identifier,
        'family' => 'shirts',
        'categories' => $categories,
        'values' => $values,
    ])->assertCreated();

    test()->postJson("/showroom/products/{$identifier}/transitions", ['transition' => 'publish'])->assertOk();
}

/**
 * @return list<array<string, mixed>>
 */
function deliveredTo(string $path): array
{
    $events = [];

    foreach (Http::recorded() as [$request]) {
        /** @var Request $request */
        if (str_ends_with($request->url(), '/'.$path) && ($request->data()['type'] ?? null) === 'events') {
            $events = [...$events, ...$request->data()['events']];
        }
    }

    return $events;
}

it('pushes a published product to the vendors whose categories it is in', function (): void {
    vendorSubscription(['path' => 'tools', 'filter' => ['categories' => ['tools']]]);
    vendorSubscription(['path' => 'garden', 'filter' => ['categories' => ['garden']]]);
    vendorSubscription(['path' => 'everything', 'filter' => ['categories' => ['master']]]);

    publishedProduct('TEE-1', ['name' => Catalog::value('Classic tee'), 'price' => Catalog::value([['amount' => 19.99, 'currency' => 'USD']])]);

    expect(deliveredTo('tools'))->toHaveCount(1)
        ->and(deliveredTo('tools')[0]['identifier'])->toBe('TEE-1')
        ->and(deliveredTo('tools')[0]['type'])->toBe('changed')
        ->and(deliveredTo('garden'))->toBe([])
        // A parent category covers everything beneath it.
        ->and(deliveredTo('everything'))->toHaveCount(1);
});

it('sends only the topics that changed, and only to vendors following them', function (): void {
    vendorSubscription(['path' => 'pricing', 'topics' => ['pricing']]);
    vendorSubscription(['path' => 'content', 'topics' => ['content']]);

    publishedProduct('TEE-1', ['name' => Catalog::value('Classic tee'), 'price' => Catalog::value([['amount' => 19.99, 'currency' => 'USD']])]);

    $this->patchJson('/showroom/products/TEE-1', ['values' => ['price' => Catalog::value([['amount' => 24.99, 'currency' => 'USD']])]])->assertOk();

    // A draft edit is not published, so nothing leaves.
    expect(deliveredTo('pricing'))->toHaveCount(1);

    $this->postJson('/showroom/products/TEE-1/transitions', ['transition' => 'publish'])->assertOk();

    $pricing = deliveredTo('pricing');

    expect($pricing)->toHaveCount(2)
        ->and($pricing[1]['topics'])->toBe(['pricing'])
        ->and($pricing[1]['data'])->toHaveKey('pricing')
        ->and($pricing[1]['data'])->not->toHaveKey('content')
        ->and($pricing[1]['data']['pricing']['values']['price'][0]['data'])->toBe([['amount' => '24.99', 'currency' => 'USD']])
        // The content vendor got the first publication, not the price change.
        ->and(deliveredTo('content'))->toHaveCount(1);
});

it('tells vendors to forget an unpublished product', function (): void {
    vendorSubscription(['path' => 'hooks', 'format' => 'thin']);

    publishedProduct('TEE-1', ['name' => Catalog::value('Classic tee')]);
    $this->postJson('/showroom/products/TEE-1/transitions', ['transition' => 'unpublish'])->assertOk();

    expect(array_column(deliveredTo('hooks'), 'type'))->toBe(['changed', 'removed']);
});

it('tells vendors to forget a deleted product', function (): void {
    vendorSubscription(['path' => 'hooks']);

    publishedProduct('TEE-1', ['name' => Catalog::value('Classic tee')]);
    $this->deleteJson('/showroom/products/TEE-1')->assertNoContent();

    expect(array_column(deliveredTo('hooks'), 'type'))->toBe(['changed', 'removed']);
});

it('pushes linked assets with a URL and checksum, never the storage path', function (): void {
    vendorSubscription(['path' => 'assets', 'topics' => ['assets']]);

    publishedProduct('TEE-1', ['name' => Catalog::value('Classic tee')]);

    Storage::disk('assets')->put('incoming/front.jpg', 'jpeg-bytes');
    $this->postJson('/showroom/assets', ['code' => 'tee-front', 'path' => 'incoming/front.jpg'])->assertCreated();
    $this->postJson('/showroom/assets/tee-front/links', ['type' => 'product', 'target' => 'TEE-1', 'role' => 'front'])->assertSuccessful();

    $delivered = deliveredTo('assets');

    // The first publication had nothing in assets, so this vendor heard
    // nothing until an asset was linked.
    expect($delivered)->toHaveCount(1);

    $asset = $delivered[0]['data']['assets']['assets'][0] ?? null;

    expect($asset)->not->toBeNull()
        ->and($asset['code'])->toBe('tee-front')
        ->and($asset['role'])->toBe('front')
        ->and($asset)->toHaveKeys(['url', 'checksum'])
        ->and($asset)->not->toHaveKeys(['disk', 'path']);
});

it('narrows values to the channel and locales a vendor subscribed with', function (): void {
    vendorSubscription(['path' => 'fr', 'format' => 'full', 'options' => ['channel' => 'ecommerce', 'locales' => ['fr']]]);

    publishedProduct('TEE-1', [
        'name' => Catalog::value('Classic tee'),
        'description' => [
            ['locale' => 'en', 'scope' => null, 'data' => 'Soft'],
            ['locale' => 'fr', 'scope' => null, 'data' => 'Doux'],
        ],
    ]);

    $description = deliveredTo('fr')[0]['data']['values']['description'];

    expect($description)->toBe([['locale' => 'fr', 'scope' => null, 'data' => 'Doux']]);
});

it('exports what a subscription covers for a vendor starting out', function (): void {
    Storage::fake('local');
    publishedProduct('TEE-1', ['name' => Catalog::value('Classic tee')], ['tools']);
    publishedProduct('TEE-2', ['name' => Catalog::value('Garden tee')], ['garden']);

    $subscription = vendorSubscription(['filter' => ['categories' => ['garden']]]);

    $path = app(Exporter::class)->export($subscription->id);
    $lines = array_filter(explode("\n", (string) Storage::disk('local')->get((string) $path)));

    expect($lines)->toHaveCount(1)
        ->and(json_decode((string) reset($lines), true)['identifier'])->toBe('TEE-2');
});
