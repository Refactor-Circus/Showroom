<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\DeleteProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\UpsertProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsData;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsResult;
use RefactorCircus\Showroom\Domains\Product\Events\ProductCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

/*
 * New simple products in a batch are written in bulk, beside the per-record
 * path every other record takes. Both must leave exactly the same rows, and
 * one record failing must never cost its neighbours.
 */

beforeEach(function (): void {
    Catalog::apparel();

    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    OwnerTypeModel::factory()->create(['code' => 'group', 'owns_products' => false]);
    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor']));
    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'holding', 'type' => 'group']));

    $this->postJson(route('showroom.categories.store'), ['code' => 'master'])->assertCreated();
    $this->postJson(route('showroom.categories.store'), ['code' => 'shirts', 'parent' => 'master'])->assertCreated();
    $this->postJson(route('showroom.association-types.store'), ['code' => 'x_sell'])->assertCreated();

    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'clearance', 'applies_to' => ['product'], 'sort_order' => 2]);

    // Versions name their author.
    $this->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));

    // Both paths stamp external ids synced now, compared as written.
    $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
});

/**
 * @param  list<mixed>  $records
 */
function parityUpsert(array $records): UpsertProductsResult
{
    return app(UpsertProductsAction::class)->execute(UpsertProductsData::from(['records' => $records]));
}

/**
 * Everything a product's creation wrote, by table, with ids, keys and
 * timestamps left out; external ids keep `synced_at`.
 *
 * @return array<string, mixed>
 */
function parityRows(string $identifier): array
{
    $product = (array) DB::table(Tables::name('products'))->where('identifier', $identifier)->sole();
    $id = $product['id'];
    $rows = fn (string $table, string $key, array $without, string $order): array => DB::table(Tables::name($table))
        ->where($key, $id)
        ->orderBy($order)
        ->get()
        ->map(fn (object $row): array => array_diff_key((array) $row, array_flip($without)))
        ->all();

    $versions = array_map(fn (array $version): array => [
        ...$version,
        'snapshot' => json_decode((string) $version['snapshot'], true),
        'changes' => json_decode((string) $version['changes'], true),
    ], $rows('versions', 'versionable_id', ['id', 'versionable_id', 'created_at'], 'version'));

    return [
        'product' => [
            ...array_diff_key($product, array_flip(['id', 'created_at', 'updated_at', 'changed_at'])),
            'values' => json_decode((string) $product['values'], true),
        ],
        'categories' => $rows('category_product', 'product_id', ['product_id'], 'category_id'),
        'external_ids' => $rows('external_ids', 'subject_id', ['id', 'subject_id', 'created_at', 'updated_at'], 'source'),
        'flags' => $rows('flag_assignments', 'subject_id', ['subject_id'], 'flag_id'),
        'unique_values' => $rows('product_unique_values', 'product_id', ['product_id'], 'attribute_id'),
        'versions' => $versions,
    ];
}

/**
 * @return array<string, array<string, mixed>>
 */
function parityPersisted(): array
{
    $persisted = [];

    foreach (ProductModel::query()->orderBy('identifier')->pluck('identifier') as $identifier) {
        $persisted[(string) $identifier] = parityRows((string) $identifier);
    }

    return $persisted;
}

function parityForget(): void
{
    foreach (ProductModel::query()->get() as $product) {
        app(DeleteProductAction::class)->execute($product);
    }
}

/**
 * Fail the first bulk insert of products, as a deadlock or a lost
 * connection would, so the batch has to fall back.
 */
function parityFailFirstBulkInsert(): void
{
    $failed = false;

    DB::beforeExecuting(function (string $sql) use (&$failed): void {
        if (! $failed && str_starts_with($sql, 'insert into "'.Tables::name('products').'"') && str_contains($sql, '), (')) {
            $failed = true;

            throw new RuntimeException('The bulk insert failed.');
        }
    });
}

it('creates through the bulk path exactly what CreateProductAction creates', function (array $record): void {
    app(CreateProductAction::class)->execute(CreateProductData::from($record));
    $viaAction = parityRows('TEE');

    parityForget();

    Event::fake([ProductCreatingActionEvent::class]);

    // A neighbour, so the write is a real multi-row bulk insert.
    $result = parityUpsert([$record, ['identifier' => 'NEIGHBOUR']]);

    // The bulk path, not CreateProductAction, wrote it.
    Event::assertNotDispatched(ProductCreatingActionEvent::class);

    expect($result->created)->toBe(2)
        ->and(parityRows('TEE'))->toEqual($viaAction);
})->with([
    'a full record' => [[
        'identifier' => 'TEE',
        'family' => 'shirts',
        'owner' => 'acme',
        'enabled' => false,
        'categories' => ['shirts', 'master'],
        'values' => [
            'name' => Catalog::value('Tee'),
            'description' => [['locale' => 'en', 'scope' => null, 'data' => 'Soft'], ['locale' => 'fr', 'scope' => null, 'data' => 'Doux']],
            'ean' => Catalog::value('4006381333931'),
            'pack_size' => Catalog::value('3'),
            'rating' => Catalog::value(4.5),
            'weight' => Catalog::value(['amount' => 120, 'unit' => 'gram']),
            'price' => Catalog::value([['amount' => '9.99', 'currency' => 'USD']]),
            'tags' => Catalog::value(['summer', 'sale']),
        ],
        'external_ids' => ['erp' => 'E-1', 'shop' => 'S-1', 'pim' => null],
        'flags' => ['clearance', 'restricted'],
    ]],
    'an identifier only' => [['identifier' => 'TEE']],
    'values without a family' => [['identifier' => 'TEE', 'values' => ['color' => Catalog::value('red')], 'owner' => 'acme']],
    'empty lists and maps' => [['identifier' => 'TEE', 'categories' => [], 'flags' => [], 'external_ids' => []]],
]);

it('stamps the external ids it writes in bulk synced now, as ExternalIds::write() does', function (): void {
    Event::fake([ProductCreatingActionEvent::class]);

    parityUpsert([['identifier' => 'TEE', 'external_ids' => ['erp' => 'E-1', 'shop' => 'S-1']], ['identifier' => 'CAP']]);

    Event::assertNotDispatched(ProductCreatingActionEvent::class);

    expect(ExternalIdModel::query()->orderBy('source')->get()->map(fn (ExternalIdModel $row): array => [$row->source, $row->synced_at?->toIso8601String()])->all())
        ->toBe([['erp', '2026-11-10T09:00:00+00:00'], ['shop', '2026-11-10T09:00:00+00:00']]);
});

it('persists every good record of a mixed chunk', function (): void {
    parityUpsert([['identifier' => 'OLD', 'family' => 'shirts']]);

    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, ReadOnlyFlagPolicy::class);
    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));

    $result = parityUpsert([
        ['identifier' => 'TEE', 'family' => 'shirts', 'values' => ['name' => Catalog::value('Tee')]],
        ['identifier' => 'BAD', 'family' => 'nope'],
        ['identifier' => 'ORPHAN', 'owner' => 'holding'],
        ['identifier' => 'SECRET', 'flags' => ['restricted']],
        ['identifier' => 'DUPE', 'values' => ['ean' => Catalog::value('123')]],
        ['identifier' => 'DUPE-2', 'values' => ['ean' => Catalog::value('123')]],
        ['identifier' => 'BAG', 'associations' => ['x_sell' => ['products' => ['TEE']]]],
        ['identifier' => 'OLD', 'family' => 'shirts', 'enabled' => false],
        ['identifier' => 'CAP', 'owner' => 'acme'],
    ]);

    $failures = [];

    foreach ($result->failures as $failure) {
        $failures[$failure['index']] = array_keys($failure['errors']);
    }

    expect([$result->created, $result->updated, $result->failed()])->toBe([4, 1, 4])
        ->and($failures)->toBe([1 => ['family'], 2 => ['owner'], 3 => ['flags'], 5 => ['values.ean']])
        ->and(ProductModel::query()->orderBy('identifier')->pluck('identifier')->all())->toBe(['BAG', 'CAP', 'DUPE', 'OLD', 'TEE'])
        ->and(ProductModel::query()->where('identifier', 'OLD')->sole()->enabled)->toBeFalse();
});

it('falls back to per-record writes when the bulk write fails, with the same result and rows', function (): void {
    $records = [
        ['identifier' => 'TEE', 'family' => 'shirts', 'owner' => 'acme', 'categories' => ['shirts'], 'values' => ['name' => Catalog::value('Tee'), 'ean' => Catalog::value('1')], 'external_ids' => ['erp' => 'E-1'], 'flags' => ['restricted']],
        ['identifier' => 'CAP', 'family' => 'shirts', 'values' => ['ean' => Catalog::value('1')]],
        ['identifier' => 'MUG', 'external_ids' => ['erp' => 'e-1']],
        ['identifier' => 'BAD', 'owner' => 'holding'],
        ['identifier' => 'BAG', 'values' => ['name' => Catalog::value('Bag')]],
    ];

    $bulk = parityUpsert($records);
    $viaBulk = parityPersisted();

    parityForget();

    Event::fake([ProductCreatingActionEvent::class]);
    parityFailFirstBulkInsert();

    $fallback = parityUpsert($records);

    // Every new product went through CreateProductAction instead.
    Event::assertDispatchedTimes(ProductCreatingActionEvent::class, 2);

    expect($fallback->toArray())->toEqual($bulk->toArray())
        ->and([$bulk->created, $bulk->failed()])->toBe([2, 3])
        ->and(parityPersisted())->toEqual($viaBulk);
});
