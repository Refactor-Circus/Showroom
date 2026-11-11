<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpsertAttributeOptionsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsResult;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;

/*
 * A legacy catalog resync sends the options of hundreds of select attributes,
 * mostly unchanged. An unchanged option may not cost a query, a new one only
 * its share of a bulk insert, and a changed one a single update.
 */

/**
 * The fixed cost of one batch, whatever its size: the attributes it names
 * and the options it matches, one query each.
 */
const OPTIONS_UPSERT_FIXED_QUERIES = 2;

/**
 * The most rows one insert statement writes: new options cost one query per
 * this many.
 */
const OPTIONS_UPSERT_ROWS_PER_INSERT = 200;

/**
 * The queries one changed option adds: its update.
 */
const OPTIONS_UPSERT_QUERIES_PER_UPDATED_OPTION = 1;

beforeEach(function (): void {
    foreach (['color', 'finish', 'size', 'material', 'pattern'] as $code) {
        AttributeModel::factory()->create(['code' => $code, 'type' => AttributeType::Select]);
    }
});

/**
 * `$count` records spread over the five attributes, every field filled.
 *
 * @param  array<string, mixed>  $overrides
 * @return list<array<string, mixed>>
 */
function optionsPerformanceRecords(string $prefix, int $count, array $overrides = []): array
{
    $attributes = ['color', 'finish', 'size', 'material', 'pattern'];

    return array_map(fn (int $i): array => [
        'attribute' => $attributes[$i % 5],
        'code' => sprintf('%s_%03d', $prefix, $i),
        'labels' => ['en' => sprintf('Option %s %d', $prefix, $i), 'de' => sprintf('Option %s %d', $prefix, $i)],
        'sort_order' => $i,
        ...$overrides,
    ], range(1, $count));
}

/**
 * Run one batch, counting the queries it sends to the app's connection.
 *
 * @param  list<array<string, mixed>>  $records
 * @return array{0: UpsertAttributeOptionsResult, 1: int}
 */
function optionsPerformanceUpsert(array $records): array
{
    $action = app(UpsertAttributeOptionsAction::class);
    $data = UpsertAttributeOptionsData::from(['records' => $records]);
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
    $few = optionsPerformanceRecords('a', 10);
    $many = optionsPerformanceRecords('b', 100);

    optionsPerformanceUpsert($few);
    optionsPerformanceUpsert($many);

    [$fewResult, $fewQueries] = optionsPerformanceUpsert($few);
    [$manyResult, $manyQueries] = optionsPerformanceUpsert($many);

    expect($fewResult->unchanged)->toBe(10)
        ->and($manyResult->unchanged)->toBe(100)
        ->and($fewQueries)->toBe(OPTIONS_UPSERT_FIXED_QUERIES)
        ->and($manyQueries)->toBe($fewQueries);
});

it('creates a batch of new options with one insert per few hundred, not one per option', function (): void {
    [$fewResult, $fewQueries] = optionsPerformanceUpsert(optionsPerformanceRecords('a', 10));
    [$manyResult, $manyQueries] = optionsPerformanceUpsert(optionsPerformanceRecords('b', 100));
    [$mostResult, $mostQueries] = optionsPerformanceUpsert(optionsPerformanceRecords('c', 500));

    expect($fewResult->created)->toBe(10)
        ->and($manyResult->created)->toBe(100)
        ->and($mostResult->created)->toBe(500)
        ->and($fewQueries)->toBe(OPTIONS_UPSERT_FIXED_QUERIES + 1)
        ->and($manyQueries)->toBe(OPTIONS_UPSERT_FIXED_QUERIES + 1)
        ->and($mostQueries)->toBe(OPTIONS_UPSERT_FIXED_QUERIES + (int) ceil(500 / OPTIONS_UPSERT_ROWS_PER_INSERT))
        ->and(AttributeOptionModel::query()->count())->toBe(610);
});

it('updates a batch of changed options in C queries plus one per option', function (): void {
    optionsPerformanceUpsert(optionsPerformanceRecords('a', 10));
    optionsPerformanceUpsert(optionsPerformanceRecords('b', 100));

    $changes = fn (string $prefix, int $count): array => array_map(fn (array $record): array => [
        ...$record,
        'labels' => ['en' => $record['labels']['en'].' renamed'],
        'sort_order' => $record['sort_order'] + 1,
    ], optionsPerformanceRecords($prefix, $count));

    [$fewResult, $fewQueries] = optionsPerformanceUpsert($changes('a', 10));
    [$manyResult, $manyQueries] = optionsPerformanceUpsert($changes('b', 100));

    expect($fewResult->updated)->toBe(10)
        ->and($manyResult->updated)->toBe(100)
        ->and($fewQueries)->toBe(OPTIONS_UPSERT_FIXED_QUERIES + OPTIONS_UPSERT_QUERIES_PER_UPDATED_OPTION * 10)
        ->and($manyQueries)->toBe(OPTIONS_UPSERT_FIXED_QUERIES + OPTIONS_UPSERT_QUERIES_PER_UPDATED_OPTION * 100);
});
