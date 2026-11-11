<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpsertAttributeOptionsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsData;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Actions\UpsertProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsData;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsResult;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Jobs\SyncProductIndex;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

/*
 * A resync of an ERP-scale catalog sends mostly records that have not
 * changed, and loads new ones in chunks. Neither may cost a query per record.
 */

/**
 * The most queries one upsert of unchanged records may run, whatever its size.
 */
const UPSERT_UNCHANGED_QUERY_BOUND = 40;

/**
 * The most queries one upsert of new simple products may run, whatever its
 * size; well under one a record at 100 records.
 */
const UPSERT_CREATED_QUERY_BOUND = 60;

/**
 * The most queries one upsert of products with 19 select values each may
 * run, whatever its size: the values' attributes, with their options, are
 * read once per batch (ValueValidator), never per product or per value.
 * Measured: 11 for new products, 10 for unchanged ones.
 */
const UPSERT_SELECT_VALUES_QUERY_BOUND = 11;

beforeEach(function (): void {
    Catalog::apparel();

    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor']));

    $this->postJson(route('showroom.categories.store'), ['code' => 'master'])->assertCreated();
    $this->postJson(route('showroom.categories.store'), ['code' => 'shirts', 'parent' => 'master'])->assertCreated();

    FlagModel::factory()->create(['code' => 'restricted']);

    // The index sync is the queue's work, not the batch's.
    Bus::fake([SyncProductIndex::class]);
});

/**
 * `$count` simple product records, every sent field filled.
 *
 * @return list<array<string, mixed>>
 */
function performanceRecords(string $prefix, int $count): array
{
    return array_map(fn (int $i): array => [
        'identifier' => sprintf('%s-%03d', $prefix, $i),
        'family' => 'shirts',
        'owner' => 'acme',
        'enabled' => true,
        'categories' => ['shirts'],
        'values' => [
            'name' => Catalog::value('Shirt '.$i),
            'ean' => Catalog::value(sprintf('%s%06d', $prefix, $i)),
            'pack_size' => Catalog::value(2),
            'tags' => Catalog::value(['summer']),
        ],
        'external_ids' => ['erp' => sprintf('E-%s-%d', $prefix, $i)],
        'flags' => ['restricted'],
    ], range(1, $count));
}

/**
 * @param  list<array<string, mixed>>  $records
 * @return array{0: UpsertProductsResult, 1: int}
 */
function performanceUpsert(array $records): array
{
    $action = app(UpsertProductsAction::class);
    $data = UpsertProductsData::from(['records' => $records]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $result = $action->execute($data);

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    return [$result, $queries];
}

it('re-upserts an unchanged batch in a number of queries that does not grow with it', function (): void {
    $few = performanceRecords('A', 10);
    $many = performanceRecords('B', 100);

    $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));

    performanceUpsert($few);
    performanceUpsert($many);

    Bus::fake([SyncProductIndex::class]);

    // The resync stamps every unchanged record's external ids, within the bound.
    $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));

    [$fewResult, $fewQueries] = performanceUpsert($few);
    [$manyResult, $manyQueries] = performanceUpsert($many);

    expect($fewResult->unchanged)->toBe(10)
        ->and($manyResult->unchanged)->toBe(100)
        ->and($fewQueries)->toBeLessThanOrEqual(UPSERT_UNCHANGED_QUERY_BOUND)
        ->and($manyQueries)->toBeLessThanOrEqual(UPSERT_UNCHANGED_QUERY_BOUND)
        ->and(ExternalIdModel::query()->count())->toBe(110)
        ->and(ExternalIdModel::query()->get()->map(fn (ExternalIdModel $row): ?string => $row->synced_at?->toIso8601String())->unique()->values()->all())
        ->toBe(['2026-11-10T10:00:00+00:00']);

    Bus::assertNotDispatched(SyncProductIndex::class);
});

it('creates a batch of new simple products in a number of queries that does not grow with it', function (): void {
    [$fewResult, $fewQueries] = performanceUpsert(performanceRecords('A', 10));
    [$manyResult, $manyQueries] = performanceUpsert(performanceRecords('B', 100));

    expect($fewResult->created)->toBe(10)
        ->and($manyResult->created)->toBe(100)
        ->and($fewQueries)->toBeLessThanOrEqual(UPSERT_CREATED_QUERY_BOUND)
        ->and($manyQueries)->toBeLessThanOrEqual(UPSERT_CREATED_QUERY_BOUND)
        // A query a record would already be 100.
        ->and($manyQueries)->toBeLessThan(100)
        ->and(ProductModel::query()->count())->toBe(110);

    // One sync per batch, each covering the whole batch.
    Bus::assertDispatchedTimes(SyncProductIndex::class, 2);
    Bus::assertDispatched(SyncProductIndex::class, fn (SyncProductIndex $job): bool => count($job->ids) === 100);
});

/**
 * A legacy catalog's select attributes: 19 of them in one family, with 50
 * options each, written through the option batch.
 */
function performanceSelectCatalog(): void
{
    $attributes = array_map(fn (int $i): string => sprintf('legacy_%02d', $i), range(1, 19));

    foreach ($attributes as $code) {
        app(CreateAttributeAction::class)->execute(CreateAttributeData::from(['code' => $code, 'type' => 'select']));
    }

    $options = [];

    foreach ($attributes as $code) {
        foreach (range(1, 50) as $i) {
            $options[] = ['attribute' => $code, 'code' => sprintf('option_%02d', $i), 'labels' => ['en' => 'Option '.$i]];
        }
    }

    foreach (array_chunk($options, 500) as $chunk) {
        expect(app(UpsertAttributeOptionsAction::class)->execute(UpsertAttributeOptionsData::from(['records' => $chunk]))->created)->toBe(count($chunk));
    }

    app(CreateFamilyAction::class)->execute(CreateFamilyData::from([
        'code' => 'legacy',
        'attributes' => array_map(fn (string $code): array => ['attribute' => $code], ['name', ...$attributes]),
    ]));
}

/**
 * `$count` product records of the legacy family, each with a select value
 * for every one of its 19 select attributes.
 *
 * @return list<array<string, mixed>>
 */
function performanceSelectRecords(string $prefix, int $count): array
{
    return array_map(function (int $i) use ($prefix): array {
        $values = ['name' => Catalog::value('Part '.$i)];

        foreach (range(1, 19) as $attribute) {
            $values[sprintf('legacy_%02d', $attribute)] = Catalog::value(sprintf('option_%02d', ($i + $attribute) % 50 + 1));
        }

        return ['identifier' => sprintf('%s-%03d', $prefix, $i), 'family' => 'legacy', 'values' => $values];
    }, range(1, $count));
}

it('creates products with many select values in the same number of queries at 10 records as at 100', function (): void {
    performanceSelectCatalog();

    [$fewResult, $fewQueries] = performanceUpsert(performanceSelectRecords('A', 10));
    [$manyResult, $manyQueries] = performanceUpsert(performanceSelectRecords('B', 100));

    expect($fewResult->created)->toBe(10)
        ->and($manyResult->created)->toBe(100)
        ->and($manyQueries)->toBe($fewQueries)
        ->and($manyQueries)->toBeLessThanOrEqual(UPSERT_SELECT_VALUES_QUERY_BOUND);
});

it('re-upserts unchanged products with many select values in the same number of queries at 10 records as at 100', function (): void {
    performanceSelectCatalog();

    $few = performanceSelectRecords('A', 10);
    $many = performanceSelectRecords('B', 100);

    performanceUpsert($few);
    performanceUpsert($many);

    [$fewResult, $fewQueries] = performanceUpsert($few);
    [$manyResult, $manyQueries] = performanceUpsert($many);

    expect($fewResult->unchanged)->toBe(10)
        ->and($manyResult->unchanged)->toBe(100)
        ->and($manyQueries)->toBe($fewQueries)
        ->and($manyQueries)->toBeLessThanOrEqual(UPSERT_SELECT_VALUES_QUERY_BOUND);
});
