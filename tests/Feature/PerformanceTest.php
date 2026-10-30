<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Atrium\Domains\Widgets\Services\WidgetRegistry;
use RefactorCircus\Impex\Domains\Run\Data\Resume;
use RefactorCircus\Impex\Domains\Run\Data\StepDeadline;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Mcp\Tools\ListProductModelsTool;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Domains\Search\Services\ProductDocument;
use RefactorCircus\Showroom\Domains\Search\Services\ProductIndex;
use RefactorCircus\Showroom\Domains\Workflow\Services\CompletenessCalculator;
use RefactorCircus\Showroom\Impex\Actions\ExportProductPages;
use RefactorCircus\Showroom\Jobs\QueueProductSyncs;
use RefactorCircus\Showroom\Jobs\SyncProductIndex;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    Catalog::apparel();

    $this->postJson('/showroom/owner-types', ['code' => 'vendor'])->assertCreated();
    $this->patchJson('/showroom/owner-types/vendor', ['parent_types' => ['vendor']])->assertOk();
    $this->postJson('/showroom/owners', ['code' => 'acme', 'type' => 'vendor'])->assertCreated();
    $this->postJson('/showroom/owners', ['code' => 'acme-tools', 'type' => 'vendor', 'parent' => 'acme'])->assertCreated();
    $this->postJson('/showroom/categories', ['code' => 'master'])->assertCreated();
    $this->postJson('/showroom/categories', ['code' => 'shirts', 'parent' => 'master'])->assertCreated();
    $this->postJson('/showroom/association-types', ['code' => 'x_sell'])->assertCreated();
});

/**
 * Seed `$count` simple products and `$count` two-level variants, each with
 * an owner, categories and associations of its own or inherited.
 */
function seedCatalog(int $count, int $from = 1): void
{
    foreach (range($from, $from + $count - 1) as $i) {
        test()->postJson('/showroom/product-models', [
            'code' => 'polo-'.$i, 'family_variant' => 'shirts_by_color_size', 'owner' => 'acme-tools', 'categories' => ['shirts'],
            'values' => ['name' => Catalog::value('Polo '.$i)],
        ])->assertCreated();
        test()->postJson('/showroom/product-models', [
            'code' => 'polo-'.$i.'-red', 'parent' => 'polo-'.$i, 'categories' => ['master'],
            'values' => ['color' => Catalog::value('red')],
        ])->assertCreated();
        test()->postJson('/showroom/products', [
            'identifier' => sprintf('S-%03d', $i), 'family' => 'shirts', 'owner' => 'acme', 'categories' => ['shirts'],
            'values' => ['name' => Catalog::value('Simple '.$i)],
        ])->assertCreated();
        test()->postJson('/showroom/products', [
            'identifier' => sprintf('V-%03d', $i), 'parent' => 'polo-'.$i.'-red',
            'values' => ['size' => Catalog::value('m')],
            'associations' => ['x_sell' => ['products' => [sprintf('S-%03d', $i)]]],
        ])->assertCreated();
    }
}

/**
 * How many queries a callback runs.
 */
function queriesDuring(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());

    DB::disableQueryLog();

    return $count;
}

it('lists products in a fixed number of queries, whatever the page holds', function (): void {
    seedCatalog(1);
    $few = queriesDuring(fn () => test()->getJson('/showroom/products?per_page=100')->assertOk()->assertJsonCount(2, 'data'));

    seedCatalog(4, 2);
    $many = queriesDuring(fn () => test()->getJson('/showroom/products?per_page=100')->assertOk()->assertJsonCount(10, 'data'));

    expect($many)->toBe($few);

    $this->getJson('/showroom/products/V-002')->assertOk()
        ->assertJsonPath('data.owner', 'acme-tools')
        ->assertJsonPath('data.categories', ['master', 'shirts']);
});

it('lists products over MCP in a fixed number of queries', function (): void {
    seedCatalog(1);
    $few = queriesDuring(fn () => mcpTool(ListProductsTool::class, ['per_page' => 100])->assertOk());

    seedCatalog(4, 2);
    $many = queriesDuring(fn () => mcpTool(ListProductsTool::class, ['per_page' => 100])->assertOk()->assertSee('V-005'));

    expect($many)->toBe($few);
});

it('lists product models in a fixed number of queries, over the API and MCP', function (): void {
    seedCatalog(1);
    $api = queriesDuring(fn () => test()->getJson('/showroom/product-models?per_page=100')->assertOk()->assertJsonCount(2, 'data'));
    $mcp = queriesDuring(fn () => mcpTool(ListProductModelsTool::class, ['per_page' => 100])->assertOk());

    seedCatalog(4, 2);

    expect(queriesDuring(fn () => test()->getJson('/showroom/product-models?per_page=100')->assertOk()->assertJsonCount(10, 'data')))->toBe($api)
        ->and(queriesDuring(fn () => mcpTool(ListProductModelsTool::class, ['per_page' => 100])->assertOk()))->toBe($mcp);

    $this->getJson('/showroom/product-models/polo-2-red')->assertOk()
        ->assertJsonPath('data.owner', 'acme-tools')
        ->assertJsonPath('data.categories', ['master', 'shirts']);
});

/**
 * Run the export step until it finishes, yielding after every page.
 *
 * @param  array<string, mixed>  $query
 * @return array{result: array{parts: int, count: int}, queries: array<int, int>, cursors: array<int, string|null>}
 */
function exportInSteps(array $query, bool $published = false, ?string $cursor = null): array
{
    $queries = [];
    $cursors = [];

    while (true) {
        $action = new ExportProductPages;
        // Already past its deadline: it yields after each page.
        $action->withCheckpoint($cursor, StepDeadline::in(0, 0));

        $result = null;
        $queries[] = queriesDuring(function () use ($action, $query, $published, &$result): void {
            $result = $action->execute($query, 'run-1', $published);
        });

        if (! $result instanceof Resume) {
            return ['result' => $result, 'queries' => $queries, 'cursors' => $cursors];
        }

        $cursor = $cursors[] = $result->cursor;
    }
}

/**
 * @return array<int, string>
 */
function exportedIdentifiers(int $parts): array
{
    $identifiers = [];

    foreach (range(1, $parts) as $page) {
        $file = ExportProductPages::part('run-1', $page);

        foreach (array_filter(explode("\n", (string) Storage::disk('assets')->get($file))) as $line) {
            $identifiers[] = json_decode($line, true)['identifier'];
        }
    }

    return $identifiers;
}

it('exports page by page by key, counting nothing', function (): void {
    config()->set('showroom.media.disk', 'assets');
    config()->set('showroom.impex.export_page_size', 3);
    Storage::fake('assets');

    seedCatalog(4);

    $export = exportInSteps([]);

    expect($export['result'])->toBe(['parts' => 3, 'count' => 8])
        ->and(exportedIdentifiers(3))->toBe(['S-001', 'S-002', 'S-003', 'S-004', 'V-001', 'V-002', 'V-003', 'V-004'])
        ->and(json_decode((string) $export['cursors'][0], true))->toBe(['page' => 2, 'count' => 3, 'after' => ['S-003', ProductModel::query()->where('identifier', 'S-003')->value('id')]]);

    // Nothing counts the matches.
    DB::enableQueryLog();
    exportInSteps([]);
    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql): bool => str_contains(strtolower($sql), 'count('))->all())->toBe([]);

    $record = json_decode(explode("\n", (string) Storage::disk('assets')->get(ExportProductPages::part('run-1', 2)))[1], true);

    expect($record)->toMatchArray([
        'identifier' => 'V-001',
        'owner' => 'acme-tools',
        'categories' => ['master', 'shirts'],
        'associations' => ['x_sell' => ['products' => ['S-001'], 'product_models' => []]],
    ]);
});

it('exports a page in a fixed number of queries, whatever it holds', function (): void {
    config()->set('showroom.media.disk', 'assets');
    config()->set('showroom.impex.export_page_size', 100);
    Storage::fake('assets');

    seedCatalog(1);
    $few = exportInSteps([])['queries'];

    seedCatalog(4, 2);
    $many = exportInSteps([]);

    expect($many['result']['count'])->toBe(10)
        ->and($many['queries'])->toBe($few);
});

it('resumes an export from a cursor that names only its page', function (): void {
    config()->set('showroom.media.disk', 'assets');
    config()->set('showroom.impex.export_page_size', 3);
    Storage::fake('assets');

    seedCatalog(4);

    // Written before keys were kept: page 2, three records so far.
    $export = exportInSteps([], cursor: '{"page":2,"count":3}');

    expect($export['result'])->toBe(['parts' => 3, 'count' => 8])
        // Parts 2 and 3: the rest, from where page 1 ended.
        ->and(exportedIdentifiers(3))->toBe(['S-004', 'V-001', 'V-002', 'V-003', 'V-004']);
});

it('exports published versions with one version query a page', function (): void {
    config()->set('showroom.media.disk', 'assets');
    config()->set('showroom.impex.export_page_size', 100);
    config()->set('showroom.workflow.require_approval', false);
    Storage::fake('assets');

    seedCatalog(3);

    foreach (['S-001', 'S-002', 'V-003'] as $identifier) {
        $this->postJson('/showroom/products/'.$identifier.'/transitions', ['transition' => 'publish'])->assertOk();
    }

    DB::enableQueryLog();
    $export = exportInSteps([], published: true);
    $versions = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql): bool => str_contains($sql, Tables::name('versions')))->count();

    expect($export['result'])->toBe(['parts' => 1, 'count' => 3])
        ->and(exportedIdentifiers(1))->toBe(['S-001', 'S-002', 'V-003'])
        ->and($versions)->toBe(1);
});

it('builds search documents for a batch in a fixed number of queries', function (): void {
    seedCatalog(1);

    $load = fn () => ProductModel::query()
        ->with(['family', 'owner', 'categories', 'parent.owner', 'parent.categories', 'parent.parent.owner', 'parent.parent.categories', 'completeness.channel', 'completeness.locale'])
        ->orderBy('identifier')
        ->get();

    $products = $load();
    $few = queriesDuring(fn () => ProductDocument::many($products));

    seedCatalog(4, 2);
    $products = $load();
    $documents = [];
    $many = queriesDuring(function () use ($products, &$documents): void {
        $documents = ProductDocument::many($products);
    });

    $variant = collect($documents)->firstWhere('identifier', 'V-002');

    expect($many)->toBe($few)->toBeLessThanOrEqual(3)
        ->and($variant['owners'])->toBe(['acme', 'acme-tools'])
        ->and($variant['category_tree'])->toBe(['master', 'shirts'])
        ->and(ProductDocument::from($products->firstWhere('identifier', 'V-002')))->toBe($variant);
});

it('scores completeness for a batch with one delete and one requirements query a family', function (): void {
    seedCatalog(3);

    $products = ProductModel::query()->with(['family', 'parent.parent'])->get();
    $before = DB::table(Tables::name('product_completeness'))->orderBy('product_id')->orderBy('channel_id')->orderBy('locale_id')->get()->map(fn (object $row): array => (array) $row)->all();

    DB::enableQueryLog();
    app(CompletenessCalculator::class)->refresh($products);
    $log = collect(DB::getQueryLog())->pluck('query');

    expect($log->filter(fn (string $sql): bool => str_starts_with(strtolower($sql), 'delete'))->count())->toBe(1)
        ->and($log->filter(fn (string $sql): bool => str_contains($sql, Tables::name('family_attributes')))->count())->toBe(1);

    $after = DB::table(Tables::name('product_completeness'))->orderBy('product_id')->orderBy('channel_id')->orderBy('locale_id')->get()->map(fn (object $row): array => array_diff_key((array) $row, ['updated_at' => true]))->all();

    expect($after)->toBe(array_map(fn (array $row): array => array_diff_key($row, ['updated_at' => true]), $before));
});

it('holds back syncs while deferring, then queues them together', function (): void {
    seedCatalog(2);
    Bus::fake([SyncProductIndex::class]);

    $index = app(ProductIndex::class);
    $ids = ProductModel::query()->orderBy('identifier')->pluck('id')->all();

    $result = $index->deferring(function () use ($ids): string {
        foreach ($ids as $id) {
            app(ProductIndex::class)->queue([$id]);
        }

        // Nested calls join the outer one.
        app(ProductIndex::class)->deferring(fn () => app(ProductIndex::class)->queue([$ids[0]]));

        Bus::assertNotDispatched(SyncProductIndex::class);

        return 'done';
    });

    expect($result)->toBe('done');

    Bus::assertDispatchedTimes(SyncProductIndex::class, 1);
    Bus::assertDispatched(SyncProductIndex::class, fn (SyncProductIndex $job): bool => $job->ids === $ids);

    // Outside, each call is sent on its own again.
    $index->queue([$ids[0]]);
    Bus::assertDispatchedTimes(SyncProductIndex::class, 2);
});

it('leaves a channel label change out of product syncs, and lists the catalogue in a queued job', function (): void {
    seedCatalog(1);
    Bus::fake([QueueProductSyncs::class, SyncProductIndex::class]);

    $this->patchJson('/showroom/channels/ecommerce', ['labels' => ['en' => 'Web shop']])->assertOk();

    Bus::assertNotDispatched(QueueProductSyncs::class);

    $this->patchJson('/showroom/channels/ecommerce', ['currencies' => ['EUR']])->assertOk();

    Bus::assertDispatched(QueueProductSyncs::class, fn (QueueProductSyncs $job): bool => $job->scope === SyncScope::All);
    // The request itself walks no products.
    Bus::assertNotDispatched(SyncProductIndex::class);
});

it('lists the products of an owner or category subtree that moved in a queued job', function (): void {
    seedCatalog(2);
    $this->postJson('/showroom/owners', ['code' => 'other', 'type' => 'vendor'])->assertCreated();
    $this->postJson('/showroom/categories', ['code' => 'web'])->assertCreated();

    Bus::fake([QueueProductSyncs::class]);

    $this->patchJson('/showroom/owners/acme-tools', ['parent' => 'other'])->assertOk();
    $this->patchJson('/showroom/categories/shirts', ['parent' => 'web'])->assertOk();

    Bus::assertDispatched(QueueProductSyncs::class, fn (QueueProductSyncs $job): bool => $job->scope === SyncScope::Owner);
    Bus::assertDispatched(QueueProductSyncs::class, fn (QueueProductSyncs $job): bool => $job->scope === SyncScope::Category);

    $index = app(ProductIndex::class);
    $identifiers = fn (SyncScope $scope, string $id): array => ProductModel::query()
        ->whereKey($index->productsWithin($scope, $id)?->pluck('id')->all() ?? [])
        ->orderBy('identifier')
        ->pluck('identifier')
        ->all();

    $owner = (string) DB::table(Tables::name('owners'))->where('code', 'acme-tools')->value('id');
    $category = (string) DB::table(Tables::name('categories'))->where('code', 'shirts')->value('id');

    // The variants inherit the owner from their root model; the simple
    // products and the variants both sit in "shirts", directly or inherited.
    expect($identifiers(SyncScope::Owner, $owner))->toBe(['V-001', 'V-002'])
        ->and($identifiers(SyncScope::Category, $category))->toBe(['S-001', 'S-002', 'V-001', 'V-002'])
        ->and($index->productsWithin(SyncScope::Owner, 'missing'))->toBeNull();
});

it('adds no query to a list screen while nothing is registered on it', function (string $key, string $subject, string $row): void {
    $html = '';

    // Everything a list view does with its screen: resolve it, then render
    // the tab bar, the panel and every slot, row.actions once per row.
    $queries = queriesDuring(function () use ($key, $subject, $row, &$html): void {
        $resolved = app(ScreenExtensions::class)->resolve(Screens::list($key, $subject), request());

        $html = Blade::render(<<<'BLADE'
            <x-atrium::screen-sections :screen="$resolved" slot="header.actions" />
            <x-atrium::screen-tabs :screen="$resolved" />
            <x-atrium::screen-tab-panel :screen="$resolved" />
            <x-atrium::screen-sections :screen="$resolved" slot="before" />
            @foreach ($rows as $row)
                <x-atrium::screen-sections :screen="$resolved" slot="row.actions" :row="$row" />
            @endforeach
            <x-atrium::screen-sections :screen="$resolved" slot="after" />
            BLADE, ['resolved' => $resolved, 'rows' => [new $row, new $row]]);
    });

    expect($queries)->toBe(0)
        ->and(trim($html))->toBe('');
})->with([
    'products' => ['showroom.products', ProductModel::class, ProductModel::class],
    'product models' => ['showroom.product-models', ProductModelModel::class, ProductModelModel::class],
    'channels' => ['showroom.channels', ChannelModel::class, ChannelModel::class],
    'assets' => ['showroom.assets', AssetModel::class, AssetModel::class],
    'categories' => ['showroom.categories', CategoryModel::class, CategoryModel::class],
    'owner types' => ['showroom.owner-types', OwnerTypeModel::class, OwnerTypeModel::class],
    'owners' => ['showroom.owners', OwnerModel::class, OwnerModel::class],
    'families' => ['showroom.families', FamilyModel::class, FamilyModel::class],
    'attributes' => ['showroom.attributes', AttributeModel::class, AttributeModel::class],
    'attribute groups' => ['showroom.attribute-groups', AttributeGroupModel::class, AttributeGroupModel::class],
    'association types' => ['showroom.association-types', AssociationTypeModel::class, AssociationTypeModel::class],
    'transfers' => ['showroom.transfers', ProductModel::class, RunModel::class],
]);

it('counts products per status in one query, and keeps completeness figures briefly', function (): void {
    seedCatalog(2);
    $this->postJson('/showroom/products/S-001/transitions', ['transition' => 'submit'])->assertOk();

    $resolve = fn (string $key): array => app(WidgetRegistry::class)->get($key)?->resolveData() ?? [];
    $status = [];

    expect(queriesDuring(function () use ($resolve, &$status): void {
        $status = $resolve('showroom.product-status');
    }))->toBe(1)
        ->and($status['counts'])->toMatchArray(['draft' => 3, 'in_review' => 1])
        ->and($status['published'])->toBe(0);

    $first = $resolve('showroom.completeness');

    expect(queriesDuring(fn () => $resolve('showroom.completeness')))->toBe(0)
        ->and($resolve('showroom.completeness'))->toBe($first);

    config()->set('showroom.atrium.widget_cache_seconds', 0);

    expect(queriesDuring(fn () => $resolve('showroom.completeness')))->toBe(1);
});
