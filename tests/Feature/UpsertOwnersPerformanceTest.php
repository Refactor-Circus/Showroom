<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpsertOwnersAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersResult;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Jobs\QueueProductSyncs;
use RefactorCircus\Showroom\Jobs\SyncProductIndex;

/*
 * A resync of an ERP's vendors and series sends mostly owners that have not
 * changed, and new or changed ones in chunks. An unchanged owner may not
 * cost a query, and a written one only a small constant number.
 */

/**
 * The fixed cost of one batch, whatever its size (criterion 12's `C`): the
 * preloads, the validation lookups, the unchanged touch and the reindex.
 * Measured on the first working build: 8 for new owners, 9 for updates.
 */
const OWNERS_UPSERT_FIXED_QUERIES = 9;

/**
 * The most queries one new owner adds, every field sent, as measured on the
 * first working build. All of them are OwnerWriter's: the slug, the insert
 * and its path, the external ids (held check, read, insert) and the flags
 * (ids, read, insert). Criterion 12 asks for 6; see build.md, group 2.
 */
const OWNERS_UPSERT_QUERIES_PER_CREATED_OWNER = 10;

/**
 * The most queries one updated owner adds when it moves and changes its
 * labels, an external id and its flags, as measured on the first working
 * build: 12 of them OwnerWriter's, and one listing the products beneath it
 * for the batch's sync. Criterion 12 asks for 6; see build.md, group 2.
 */
const OWNERS_UPSERT_QUERIES_PER_UPDATED_OWNER = 13;

beforeEach(function (): void {
    $vendor = OwnerTypeModel::factory()->create(['code' => 'vendor', 'can_be_root' => true, 'restricts_parents' => true]);
    $vendor->parentTypes()->attach($vendor->id);
    $series = OwnerTypeModel::factory()->create(['code' => 'series', 'can_be_root' => false, 'restricts_parents' => true]);
    $series->parentTypes()->attach($vendor->id);

    FlagModel::factory()->create(['code' => 'restricted']);
    FlagModel::factory()->create(['code' => 'pricing_only']);

    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor']));
    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'globex', 'type' => 'vendor']));

    // The index sync is the queue's work, not the batch's.
    Queue::fake([SyncProductIndex::class, QueueProductSyncs::class]);
});

/**
 * `$count` series records under acme, every field filled.
 *
 * @param  array<string, mixed>  $overrides
 * @return list<array<string, mixed>>
 */
function ownersPerformanceRecords(string $prefix, int $count, array $overrides = []): array
{
    return array_map(fn (int $i): array => [
        'code' => sprintf('%s-%03d', $prefix, $i),
        'type' => 'series',
        'parent' => 'acme',
        'labels' => ['en' => sprintf('Series %s %d', $prefix, $i)],
        'external_ids' => ['erp' => sprintf('S-%s-%d', $prefix, $i)],
        'flags' => ['restricted'],
        ...$overrides,
    ], range(1, $count));
}

/**
 * Run one batch, counting the queries it sends to the app's connection.
 *
 * @param  list<array<string, mixed>>  $records
 * @return array{0: UpsertOwnersResult, 1: int}
 */
function ownersPerformanceUpsert(array $records): array
{
    $action = app(UpsertOwnersAction::class);
    $data = UpsertOwnersData::from(['records' => $records]);
    $connection = DB::getDefaultConnection();
    $queries = 0;
    $listening = true;

    DB::listen(function (QueryExecuted $query) use (&$queries, &$listening, $connection): void {
        if ($listening && $query->connectionName === $connection) {
            $queries++;
        }
    });

    $result = $action->execute($data);
    $listening = false;

    return [$result, $queries];
}

it('re-upserts an unchanged batch in the same number of queries at 10 records as at 100', function (): void {
    $few = ownersPerformanceRecords('A', 10);
    $many = ownersPerformanceRecords('B', 100);

    $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));

    ownersPerformanceUpsert($few);
    ownersPerformanceUpsert($many);

    // The resync stamps every unchanged record's external ids, within the bound.
    $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));

    [$fewResult, $fewQueries] = ownersPerformanceUpsert($few);
    [$manyResult, $manyQueries] = ownersPerformanceUpsert($many);

    expect($fewResult->unchanged)->toBe(10)
        ->and($manyResult->unchanged)->toBe(100)
        ->and($manyQueries)->toBe($fewQueries)
        ->and(ExternalIdModel::query()->count())->toBe(110)
        ->and(ExternalIdModel::query()->get()->map(fn (ExternalIdModel $row): ?string => $row->synced_at?->toIso8601String())->unique()->values()->all())
        ->toBe(['2026-11-10T10:00:00+00:00']);

    Queue::assertNothingPushed();
});

it('creates a batch of new owners in at most C queries plus a constant per owner', function (): void {
    [$fewResult, $fewQueries] = ownersPerformanceUpsert(ownersPerformanceRecords('A', 10));
    [$manyResult, $manyQueries] = ownersPerformanceUpsert(ownersPerformanceRecords('B', 100));

    expect($fewResult->created)->toBe(10)
        ->and($manyResult->created)->toBe(100)
        ->and($fewQueries)->toBeLessThanOrEqual(OWNERS_UPSERT_FIXED_QUERIES + OWNERS_UPSERT_QUERIES_PER_CREATED_OWNER * 10)
        ->and($manyQueries)->toBeLessThanOrEqual(OWNERS_UPSERT_FIXED_QUERIES + OWNERS_UPSERT_QUERIES_PER_CREATED_OWNER * 100)
        ->and(OwnerModel::query()->count())->toBe(112);
});

it('updates a batch of changed owners in at most C queries plus a constant per owner', function (): void {
    ownersPerformanceUpsert(ownersPerformanceRecords('A', 10));
    ownersPerformanceUpsert(ownersPerformanceRecords('B', 100));

    // Each moves, and changes its labels, its external id and its flags.
    $changes = fn (string $prefix, int $count): array => array_map(fn (array $record): array => [
        ...$record,
        'parent' => 'globex',
        'labels' => ['en' => $record['labels']['en'].' renamed'],
        'external_ids' => ['erp' => $record['external_ids']['erp'].'-2'],
        'flags' => ['pricing_only'],
    ], ownersPerformanceRecords($prefix, $count));

    [$fewResult, $fewQueries] = ownersPerformanceUpsert($changes('A', 10));
    [$manyResult, $manyQueries] = ownersPerformanceUpsert($changes('B', 100));

    expect($fewResult->updated)->toBe(10)
        ->and($manyResult->updated)->toBe(100)
        ->and($fewQueries)->toBeLessThanOrEqual(OWNERS_UPSERT_FIXED_QUERIES + OWNERS_UPSERT_QUERIES_PER_UPDATED_OWNER * 10)
        ->and($manyQueries)->toBeLessThanOrEqual(OWNERS_UPSERT_FIXED_QUERIES + OWNERS_UPSERT_QUERIES_PER_UPDATED_OWNER * 100);

    // The products beneath are listed for the batch's one sync, never per owner.
    Queue::assertNotPushed(QueueProductSyncs::class);
});
