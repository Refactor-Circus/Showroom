<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Showroom\Domains\Catalog\Actions\ShowCatalogStatsAction;
use RefactorCircus\Showroom\Domains\Catalog\Data\CatalogStats;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogStatsShowingActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Events\CatalogStatsShownActionEvent;
use RefactorCircus\Showroom\Domains\Catalog\Resources\CatalogStatsResource;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\CompletenessModel;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use Workbench\App\Models\User;

/**
 * Store a completeness score, as the calculator would.
 */
function scoreForStats(ProductModel $product, ChannelModel $channel, LocaleModel $locale, int $ratio): void
{
    CompletenessModel::query()->create([
        'product_id' => $product->id,
        'channel_id' => $channel->id,
        'locale_id' => $locale->id,
        'required' => 4,
        'missing' => (int) round(4 * (100 - $ratio) / 100),
        'ratio' => $ratio,
        'missing_attributes' => [],
    ]);
}

/**
 * Record a version of a product or product model at a fixed time.
 */
function versionForStats(ProductModel|ProductModelModel $subject, int $minute, ?User $author = null): VersionModel
{
    return VersionModel::query()->forceCreate([
        'versionable_type' => $subject->getMorphClass(),
        'versionable_id' => $subject->getKey(),
        'version' => 1,
        'action' => 'created',
        'snapshot' => ['code' => 'x'],
        'changes' => ['enabled' => ['old' => null, 'new' => true]],
        'comment' => null,
        'author_type' => $author?->getMorphClass(),
        'author_id' => $author === null ? null : (string) $author->getKey(),
        'created_at' => Carbon::parse('2026-11-01 09:00:00')->addMinutes($minute),
    ]);
}

/**
 * A known catalog: 3 draft, 6 in review, 2 approved and no archived
 * products, 2 of them published; scores on two channels and two locales;
 * nine versions, one of them a product model's.
 *
 * @return array{products: array<string, ProductModel>, model: ProductModelModel, author: User}
 */
function seedStatsCatalog(): array
{
    $product = fn (string $identifier, ProductStatus $status, ?int $published = null, ?string $updated = null): ProductModel => ProductModel::factory()->create([
        'identifier' => $identifier,
        'status' => $status,
        'published_version' => $published,
        'updated_at' => Carbon::parse($updated ?? '2026-11-10 12:00:00'),
    ]);

    $products = [
        'A' => $product('SKU-A', ProductStatus::Draft, published: 1),
        'B' => $product('SKU-B', ProductStatus::Draft),
        'C' => $product('SKU-C', ProductStatus::Draft),
        'D' => $product('SKU-D', ProductStatus::InReview, updated: '2026-11-05 12:00:00'),
        'E' => $product('SKU-E', ProductStatus::InReview, updated: '2026-11-03 12:00:00'),
        'F' => $product('SKU-F', ProductStatus::InReview, updated: '2026-11-01 12:00:00'),
        'G' => $product('SKU-G', ProductStatus::InReview, updated: '2026-11-04 12:00:00'),
        'H' => $product('SKU-H', ProductStatus::InReview, updated: '2026-11-02 12:00:00'),
        'I' => $product('SKU-I', ProductStatus::InReview, updated: '2026-11-06 12:00:00'),
        'K' => $product('SKU-K', ProductStatus::Approved, published: 2),
        'L' => $product('SKU-L', ProductStatus::Approved),
    ];

    // Created out of code order, so the order comes from the query.
    $web = ChannelModel::factory()->create(['code' => 'web']);
    $print = ChannelModel::factory()->create(['code' => 'print']);
    $french = LocaleModel::factory()->create(['code' => 'fr_FR']);
    $english = LocaleModel::factory()->create(['code' => 'en_GB']);

    scoreForStats($products['A'], $web, $english, 100);
    scoreForStats($products['B'], $web, $english, 100);
    scoreForStats($products['C'], $web, $english, 50);
    scoreForStats($products['A'], $web, $french, 75);
    scoreForStats($products['B'], $web, $french, 80);
    scoreForStats($products['A'], $print, $english, 100);

    $model = ProductModelModel::factory()->create(['code' => 'polo']);
    $author = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);

    versionForStats($products['A'], 1);
    versionForStats($products['B'], 2);
    versionForStats($products['C'], 3);
    versionForStats($model, 4);
    versionForStats($products['D'], 5);
    versionForStats($products['E'], 6, $author);
    versionForStats($products['F'], 7);
    versionForStats($products['G'], 8);
    versionForStats($products['H'], 9);

    return ['products' => $products, 'model' => $model, 'author' => $author];
}

it('counts products per status, a status without products included, and the published ones', function (): void {
    seedStatsCatalog();

    $stats = app(ShowCatalogStatsAction::class)->execute();

    expect($stats)->toBeInstanceOf(CatalogStats::class)
        ->and($stats->statusCounts)->toBe(['draft' => 3, 'in_review' => 6, 'approved' => 2, 'archived' => 0])
        ->and($stats->published)->toBe(2);
});

it('scores completeness per channel and locale, in code order', function (): void {
    seedStatsCatalog();

    expect(app(ShowCatalogStatsAction::class)->execute()->completeness)->toBe([
        ['channel' => 'print', 'locale' => 'en_GB', 'products' => 1, 'complete' => 1, 'average' => 100],
        // (100 + 100 + 50) / 3 = 83.3
        ['channel' => 'web', 'locale' => 'en_GB', 'products' => 3, 'complete' => 2, 'average' => 83],
        // (75 + 80) / 2 = 77.5
        ['channel' => 'web', 'locale' => 'fr_FR', 'products' => 2, 'complete' => 0, 'average' => 78],
    ]);
});

it('lists the five products waiting longest for review, and how many wait', function (): void {
    ['products' => $products] = seedStatsCatalog();

    $stats = app(ShowCatalogStatsAction::class)->execute();

    expect($stats->reviewQueue->pluck('identifier')->all())->toBe(['SKU-F', 'SKU-H', 'SKU-E', 'SKU-G', 'SKU-D'])
        ->and($stats->reviewTotal)->toBe(6);

    $queue = (new CatalogStatsResource($stats))->resolve(request())['review_queue'];

    expect($queue['total'])->toBe(6)
        ->and($queue['products'][0])->toBe([
            'id' => $products['F']->id,
            'identifier' => 'SKU-F',
            'slug' => $products['F']->slug,
            'status' => 'in_review',
            'updated_at' => Carbon::parse('2026-11-01 12:00:00')->toIso8601String(),
        ]);
});

it('lists the eight newest versions with what each is a version of', function (): void {
    ['products' => $products, 'model' => $model, 'author' => $author] = seedStatsCatalog();

    $stats = app(ShowCatalogStatsAction::class)->execute();

    expect($stats->recentVersions)->toHaveCount(8)
        ->and($stats->recentVersions->every(fn (VersionModel $version): bool => $version->relationLoaded('versionable') && $version->relationLoaded('author')))->toBeTrue();

    $versions = (new CatalogStatsResource($stats))->resolve(request())['recent_versions'];

    expect(array_column($versions, 'subject'))->toBe([
        ['type' => 'product', 'id' => $products['H']->id, 'key' => 'SKU-H'],
        ['type' => 'product', 'id' => $products['G']->id, 'key' => 'SKU-G'],
        ['type' => 'product', 'id' => $products['F']->id, 'key' => 'SKU-F'],
        ['type' => 'product', 'id' => $products['E']->id, 'key' => 'SKU-E'],
        ['type' => 'product', 'id' => $products['D']->id, 'key' => 'SKU-D'],
        ['type' => 'product_model', 'id' => $model->id, 'key' => 'polo'],
        ['type' => 'product', 'id' => $products['C']->id, 'key' => 'SKU-C'],
        ['type' => 'product', 'id' => $products['B']->id, 'key' => 'SKU-B'],
    ])
        ->and($versions[3])->toMatchArray([
            'version' => 1,
            'action' => 'created',
            'comment' => null,
            'author' => ['type' => $author->getMorphClass(), 'id' => (string) $author->getKey()],
            'changes' => ['enabled' => ['old' => null, 'new' => true]],
            'created_at' => Carbon::parse('2026-11-01 09:06:00')->toIso8601String(),
        ]);
});

it('keeps the completeness rows for showroom.stats.cache_seconds, and nothing else', function (): void {
    config()->set('showroom.stats.cache_seconds', 60);
    ['products' => $products] = seedStatsCatalog();

    $first = app(ShowCatalogStatsAction::class)->execute();

    // A new score and a new product after the first read.
    scoreForStats($products['K'], ChannelModel::factory()->create(['code' => 'app']), LocaleModel::query()->where('code', 'en_GB')->firstOrFail(), 100);
    ProductModel::factory()->create(['identifier' => 'SKU-Z', 'status' => ProductStatus::Archived]);

    $second = app(ShowCatalogStatsAction::class)->execute();

    expect(Cache::has('showroom:stats:completeness'))->toBeTrue()
        ->and($second->completeness)->toBe($first->completeness)
        ->and($second->statusCounts['archived'])->toBe(1);
});

it('reads the completeness rows fresh when showroom.stats.cache_seconds is 0 or less', function (int $seconds): void {
    config()->set('showroom.stats.cache_seconds', $seconds);
    ['products' => $products] = seedStatsCatalog();

    $first = app(ShowCatalogStatsAction::class)->execute();

    scoreForStats($products['K'], ChannelModel::factory()->create(['code' => 'app']), LocaleModel::query()->where('code', 'en_GB')->firstOrFail(), 100);

    $second = app(ShowCatalogStatsAction::class)->execute();

    expect(Cache::has('showroom:stats:completeness'))->toBeFalse()
        ->and($first->completeness)->toHaveCount(3)
        ->and($second->completeness[0])->toBe(['channel' => 'app', 'locale' => 'en_GB', 'products' => 1, 'complete' => 1, 'average' => 100]);
})->with([0, -5]);

it('answers an empty catalog with zeros and empty lists', function (): void {
    $stats = app(ShowCatalogStatsAction::class)->execute();

    expect($stats->statusCounts)->toBe(['draft' => 0, 'in_review' => 0, 'approved' => 0, 'archived' => 0])
        ->and($stats->published)->toBe(0)
        ->and($stats->completeness)->toBe([])
        ->and($stats->reviewQueue)->toHaveCount(0)
        ->and($stats->reviewTotal)->toBe(0)
        ->and($stats->recentVersions)->toHaveCount(0);
});

it('fires the showing and shown events around the read', function (): void {
    Event::fake([CatalogStatsShowingActionEvent::class, CatalogStatsShownActionEvent::class]);

    $stats = app(ShowCatalogStatsAction::class)->execute();

    Event::assertDispatched(CatalogStatsShowingActionEvent::class);
    Event::assertDispatched(CatalogStatsShownActionEvent::class, fn (CatalogStatsShownActionEvent $event): bool => $event->stats === $stats);
});
