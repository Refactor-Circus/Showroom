<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Policies\FlagPolicy;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpsertOwnersAction;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersResult;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerSavedEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerSavingEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnersUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnersUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Jobs\QueueProductSyncs;
use RefactorCircus\Showroom\Jobs\SyncProductIndex;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

/*
 * UpsertOwnersAction loads a batch of owner records in API shape: it matches
 * each to a stored owner, by code or by its id in a source (an ERP), creates
 * or updates it through OwnerWriter, and leaves alone what has not changed,
 * only stamping its external ids synced. A record that fails is reported;
 * its neighbours are still written.
 */

beforeEach(function (): void {
    // A vendor may sit at the root or under another vendor; a series only under a vendor.
    $vendor = OwnerTypeModel::factory()->create(['code' => 'vendor', 'can_be_root' => true, 'restricts_parents' => true]);
    $vendor->parentTypes()->attach($vendor->id);
    $series = OwnerTypeModel::factory()->create(['code' => 'series', 'can_be_root' => false, 'restricts_parents' => true]);
    $series->parentTypes()->attach($vendor->id);

    FlagModel::factory()->create(['code' => 'pricing_only', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 2]);
});

/**
 * @param  list<mixed>  $records
 * @param  array<string, mixed>  $options
 */
function ownersUpsert(array $records, array $options = []): UpsertOwnersResult
{
    return app(UpsertOwnersAction::class)->execute(UpsertOwnersData::from(['records' => $records, ...$options]));
}

/**
 * A vendor record as an ERP sends it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ownersUpsertRecord(string $code, array $overrides = []): array
{
    return [
        'code' => $code,
        'type' => 'vendor',
        'labels' => ['en' => ucfirst($code)],
        'external_ids' => ['erp' => 'V-'.$code],
        'flags' => ['restricted'],
        ...$overrides,
    ];
}

/**
 * The failures of a result, as index => [code, error keys].
 *
 * @return array<int, array{0: string|null, 1: list<string>}>
 */
function ownersUpsertFailures(UpsertOwnersResult $result): array
{
    $failures = [];

    foreach ($result->failures as $failure) {
        $failures[$failure['index']] = [$failure['code'], array_keys($failure['errors'])];
    }

    return $failures;
}

function ownersUpsertOwner(string $code): OwnerModel
{
    return OwnerModel::query()->with(['type', 'parent'])->where('code', $code)->sole();
}

/**
 * @return list<string>
 */
function ownersUpsertCodes(): array
{
    /** @var list<string> $codes */
    $codes = OwnerModel::query()->orderBy('code')->pluck('code')->all();

    return $codes;
}

/**
 * An owner's path with codes in place of ids: `/acme/tools/`.
 */
function ownersUpsertPath(string $code): string
{
    $ids = array_values(array_filter(explode('/', ownersUpsertOwner($code)->path)));
    $codes = OwnerModel::query()->whereKey($ids)->pluck('code', 'id');

    return '/'.implode('', array_map(fn (string $id): string => $codes[$id].'/', $ids));
}

/**
 * The external ids stored for an owner, as {source: id}.
 *
 * @return array<string, string>
 */
function ownersUpsertExternalIds(string $code): array
{
    return ExternalIdModel::query()
        ->where('subject_type', (new OwnerModel)->getMorphClass())
        ->where('subject_id', ownersUpsertOwner($code)->id)
        ->orderBy('source')
        ->pluck('external_id', 'source')
        ->all();
}

/**
 * An owner's external id rows, as {source: [synced_at, updated_at]} in ISO-8601.
 *
 * @return array<string, array{0: string|null, 1: string|null}>
 */
function ownersUpsertStamps(string $code): array
{
    return ExternalIdModel::query()
        ->where('subject_type', (new OwnerModel)->getMorphClass())
        ->where('subject_id', ownersUpsertOwner($code)->id)
        ->orderBy('source')
        ->get()
        ->mapWithKeys(fn (ExternalIdModel $row): array => [$row->source => [$row->synced_at?->toIso8601String(), $row->updated_at?->toIso8601String()]])
        ->all();
}

/**
 * The codes of an owner's own flags as stored.
 *
 * @return list<string>
 */
function ownersUpsertFlags(string $code): array
{
    /** @var list<string> $codes */
    $codes = DB::table(Tables::name('flag_assignments'))
        ->join(Tables::name('flags'), Tables::name('flags').'.id', '=', Tables::name('flag_assignments').'.flag_id')
        ->where('subject_type', (new OwnerModel)->getMorphClass())
        ->where('subject_id', ownersUpsertOwner($code)->id)
        ->orderBy('code')
        ->pluck('code')
        ->all();

    return $codes;
}

/**
 * Every owners row, ordered by code.
 *
 * @return list<array<string, mixed>>
 */
function ownersUpsertRows(): array
{
    return DB::table(Tables::name('owners'))->orderBy('code')->get()->map(fn (object $row): array => (array) $row)->all();
}

/**
 * The statements that wrote (insert, update, delete) while running a callback.
 *
 * @return list<string>
 */
function ownersUpsertWritesDuring(Closure $callback): array
{
    $writes = [];
    $listening = true;

    DB::listen(function (QueryExecuted $query) use (&$writes, &$listening): void {
        if ($listening && preg_match('/^\s*(insert|update|delete)\b/i', $query->sql) === 1) {
            $writes[] = $query->sql;
        }
    });

    $callback();
    $listening = false;

    return $writes;
}

/**
 * @param  class-string<FlagPolicy>  $policy
 */
function ownersUpsertSignInWithFlagPolicy(string $policy): void
{
    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, $policy);

    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));

    test()->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));
}

/**
 * The ids of these products, sorted.
 *
 * @param  list<string>  $identifiers
 * @return list<string>
 */
function ownersUpsertProductIds(array $identifiers): array
{
    /** @var list<string> $ids */
    $ids = ProductModel::query()->whereIn('identifier', $identifiers)->pluck('id')->sort()->values()->all();

    return $ids;
}

/**
 * Record the owner Action events, and the batch's own pair, in the order
 * they fire, as `EventName:code`.
 *
 * @return ArrayObject<int, string>
 */
function ownersUpsertRecordEvents(): ArrayObject
{
    /** @var ArrayObject<int, string> $seen */
    $seen = new ArrayObject;

    Event::listen(OwnersUpsertingActionEvent::class, fn () => $seen->append('OwnersUpserting'));
    Event::listen(OwnersUpsertedActionEvent::class, fn () => $seen->append('OwnersUpserted'));
    Event::listen(OwnerCreatingActionEvent::class, fn (OwnerCreatingActionEvent $event) => $seen->append('OwnerCreating:'.$event->data->code));
    Event::listen(OwnerCreatedActionEvent::class, fn (OwnerCreatedActionEvent $event) => $seen->append('OwnerCreated:'.$event->owner->code));
    Event::listen(OwnerUpdatingActionEvent::class, fn (OwnerUpdatingActionEvent $event) => $seen->append('OwnerUpdating:'.$event->owner->code));
    Event::listen(OwnerUpdatedActionEvent::class, fn (OwnerUpdatedActionEvent $event) => $seen->append('OwnerUpdated:'.$event->owner->code));

    return $seen;
}

/**
 * Fail the database write of one owner once it is inserted, as a lost
 * connection or a trigger would, so its transaction rolls back.
 *
 * @param  list<string>  $codes
 */
function ownersUpsertFailWritesOf(array $codes): void
{
    Event::listen(OwnerSavingEvent::class, function (OwnerSavingEvent $event) use ($codes): void {
        if ($event->owner->exists && in_array($event->owner->code, $codes, true)) {
            DB::table('owners_upsert_no_such_table')->insert(['id' => 1]);
        }
    });
}

describe('modes and matching', function (): void {
    it('creates new records and updates matched ones by code', function (): void {
        ownersUpsert([ownersUpsertRecord('acme')]);

        $result = ownersUpsert([
            ownersUpsertRecord('acme', ['labels' => ['en' => 'Acme Inc']]),
            ownersUpsertRecord('globex'),
        ]);

        expect([$result->created, $result->updated, $result->unchanged, $result->skipped, $result->failed()])->toBe([1, 1, 0, 0, 0])
            ->and(ownersUpsertCodes())->toBe(['acme', 'globex'])
            ->and(ownersUpsertOwner('acme')->labels)->toBe(['en' => 'Acme Inc'])
            ->and(ownersUpsertOwner('globex')->slug)->toBe('globex')
            ->and(ownersUpsertOwner('globex')->type->code)->toBe('vendor')
            ->and(ownersUpsertExternalIds('globex'))->toBe(['erp' => 'V-globex'])
            ->and(ownersUpsertFlags('globex'))->toBe(['restricted']);
    });

    it('skips matched records in create mode and new records in update mode', function (): void {
        ownersUpsert([ownersUpsertRecord('acme')]);

        $create = ownersUpsert([ownersUpsertRecord('acme', ['labels' => ['en' => 'Created?']]), ownersUpsertRecord('globex')], ['mode' => 'create']);
        $update = ownersUpsert([ownersUpsertRecord('acme', ['labels' => ['en' => 'Updated']]), ownersUpsertRecord('initech')], ['mode' => UpsertMode::Update->value]);

        expect([$create->created, $create->updated, $create->skipped])->toBe([1, 0, 1])
            ->and([$update->created, $update->updated, $update->skipped])->toBe([0, 1, 1])
            ->and(ownersUpsertCodes())->toBe(['acme', 'globex'])
            ->and(ownersUpsertOwner('acme')->labels)->toBe(['en' => 'Updated']);
    });

    it('matches by a source\'s external id', function (): void {
        ownersUpsert([
            ownersUpsertRecord('acme', ['external_ids' => ['erp' => 'V-1']]),
            ownersUpsertRecord('initech', ['external_ids' => ['erp' => 'V-3']]),
        ]);

        $result = ownersUpsert([
            ['code' => 'acme', 'external_ids' => ['erp' => 'V-1'], 'labels' => ['en' => 'Acme Inc']],
            // Matched by its id alone: the code may be left out.
            ['external_ids' => ['erp' => 'V-3'], 'flags' => []],
            ownersUpsertRecord('globex', ['external_ids' => ['erp' => 'V-2']]),
        ], ['match' => 'erp']);

        expect([$result->created, $result->updated, $result->failed()])->toBe([1, 2, 0])
            ->and(ownersUpsertOwner('acme')->labels)->toBe(['en' => 'Acme Inc'])
            ->and(ownersUpsertFlags('initech'))->toBe([])
            ->and(ownersUpsertCodes())->toBe(['acme', 'globex', 'initech']);
    });

    it('refuses to change the code or type of a matched owner, and writes nothing for it', function (array $record, string $key): void {
        ownersUpsert([ownersUpsertRecord('acme', ['external_ids' => ['erp' => 'V-1']])]);
        $before = ownersUpsertRows();

        $result = ownersUpsert([$record, ownersUpsertRecord('globex')], ['match' => 'erp']);

        expect(ownersUpsertFailures($result))->toBe([0 => [$record['code'] ?? 'acme', [$key]]])
            ->and($result->created)->toBe(1)
            ->and(array_values(array_filter(ownersUpsertRows(), fn (array $row): bool => $row['code'] !== 'globex')))->toBe($before);
    })->with([
        'the code' => [['code' => 'renamed', 'type' => 'vendor', 'external_ids' => ['erp' => 'V-1'], 'labels' => ['en' => 'Renamed']], 'code'],
        'the type' => [['code' => 'acme', 'type' => 'series', 'external_ids' => ['erp' => 'V-1'], 'labels' => ['en' => 'Renamed']], 'type'],
    ]);

    it('refuses an unknown mode or a match that is not a source name', function (array $options, string $key): void {
        expect(fn () => ownersUpsert([ownersUpsertRecord('acme')], $options))
            ->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey($key));
    })->with([
        'mode' => [['mode' => 'replace'], 'mode'],
        'match' => [['match' => 'ERP System'], 'match'],
    ]);
});

describe('validation', function (): void {
    it('reports each failing record by index, code and errors, and writes the rest', function (): void {
        $result = ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('bad-type', ['type' => 'nope']),
            'not an object',
            ['a', 'list'],
            ownersUpsertRecord('', ['code' => null]),
            ownersUpsertRecord('bad-flag', ['flags' => ['nope']]),
            ownersUpsertRecord('read-only', ['slug' => 'mine']),
            ownersUpsertRecord('globex'),
        ]);

        expect([$result->created, $result->failed()])->toBe([2, 6])
            ->and(ownersUpsertFailures($result))->toBe([
                1 => ['bad-type', ['type']],
                2 => [null, ['record']],
                3 => [null, ['record']],
                4 => [null, ['code']],
                5 => ['bad-flag', ['flags.0']],
                6 => ['read-only', ['slug']],
            ])
            ->and($result->failures[0]['errors']['type'][0])->toBe('The selected type is invalid.')
            ->and($result->failures[1]['errors']['record'][0])->toBe('The record must be an object.')
            ->and(ownersUpsertCodes())->toBe(['acme', 'globex']);
    });
});

describe('fields left out (D-002)', function (): void {
    beforeEach(function (): void {
        ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('globex'),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme', 'external_ids' => ['erp' => 'S-1', 'shop' => 'T-1']]),
            ownersUpsertRecord('initech', ['parent' => 'acme']),
        ]);
    });

    it('keeps the parent, labels, external ids and flags a record leaves out', function (): void {
        $result = ownersUpsert([['code' => 'tools', 'external_ids' => ['erp' => 'S-2']]]);

        expect($result->updated)->toBe(1)
            ->and(ownersUpsertPath('tools'))->toBe('/acme/tools/')
            ->and(ownersUpsertOwner('tools')->labels)->toBe(['en' => 'Tools'])
            ->and(ownersUpsertExternalIds('tools'))->toBe(['erp' => 'S-2', 'shop' => 'T-1'])
            ->and(ownersUpsertFlags('tools'))->toBe(['restricted']);
    });

    it('moves an owner to the root on an explicit null parent', function (): void {
        $result = ownersUpsert([['code' => 'initech', 'parent' => null]]);

        expect($result->updated)->toBe(1)
            ->and(ownersUpsertOwner('initech')->parent_id)->toBeNull()
            ->and(ownersUpsertOwner('initech')->depth)->toBe(0)
            ->and(ownersUpsertPath('initech'))->toBe('/initech/');
    });

    it('finds a record whose sent fields equal the stored owner\'s unchanged, whatever it leaves out', function (): void {
        $result = ownersUpsert([
            ['code' => 'acme'],
            ['code' => 'tools', 'type' => 'series', 'parent' => 'acme', 'external_ids' => ['shop' => 'T-1']],
            ['code' => 'globex', 'labels' => ['en' => 'Globex'], 'external_ids' => ['erp' => 'V-globex']],
            ['code' => 'initech', 'parent' => 'acme', 'flags' => ['restricted']],
        ]);

        expect([$result->unchanged, $result->updated, $result->failed()])->toBe([4, 0, 0]);
    });
});

describe('duplicates (D-003)', function (): void {
    it('refuses the second record with a code, whatever became of the first', function (array $first, array $options, array $counts): void {
        ownersUpsert([ownersUpsertRecord('stored')]);

        $result = ownersUpsert([$first, ownersUpsertRecord($first['code'], ['labels' => ['en' => 'Second']])], $options);

        expect([$result->created, $result->updated, $result->unchanged, $result->skipped])->toBe($counts)
            ->and(ownersUpsertFailures($result)[1])->toBe([$first['code'], ['code']])
            ->and($result->failures[array_key_last($result->failures)]['errors']['code'][0])->toContain('Another record of the batch')
            ->and(OwnerModel::query()->where('labels', json_encode(['en' => 'Second']))->exists())->toBeFalse();
    })->with([
        'created' => [ownersUpsertRecord('acme'), [], [1, 0, 0, 0]],
        'updated' => [ownersUpsertRecord('stored', ['labels' => ['en' => 'First']]), [], [0, 1, 0, 0]],
        'unchanged' => [ownersUpsertRecord('stored'), [], [0, 0, 1, 0]],
        'skipped' => [ownersUpsertRecord('stored'), ['mode' => 'create'], [0, 0, 0, 1]],
        'failed' => [ownersUpsertRecord('acme', ['type' => 'nope']), [], [0, 0, 0, 0]],
    ]);

    it('refuses the second record matching the same external id', function (): void {
        ownersUpsert([ownersUpsertRecord('acme', ['external_ids' => ['erp' => 'V-1']])]);

        $result = ownersUpsert([
            ['code' => 'acme', 'external_ids' => ['erp' => 'V-1'], 'labels' => ['en' => 'First']],
            ['external_ids' => ['erp' => 'V-1'], 'labels' => ['en' => 'Second']],
        ], ['match' => 'erp']);

        expect($result->updated)->toBe(1)
            ->and(ownersUpsertFailures($result))->toBe([1 => ['acme', ['code']]])
            ->and(ownersUpsertOwner('acme')->labels)->toBe(['en' => 'First']);
    });

    it('adds a duplicate to nothing: a child naming its code finds no parent', function (): void {
        $result = ownersUpsert([
            ownersUpsertRecord('acme', ['type' => 'nope']),
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
        ]);

        expect($result->created)->toBe(0)
            ->and(ownersUpsertFailures($result))->toBe([0 => ['acme', ['type']], 1 => ['acme', ['code']], 2 => ['tools', ['parent']]])
            ->and(ownersUpsertCodes())->toBe([]);
    });
});

describe('parents within the batch', function (): void {
    it('places a record under an owner an earlier record created', function (): void {
        $result = ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
        ]);

        expect($result->created)->toBe(2)
            ->and(ownersUpsertPath('tools'))->toBe('/acme/tools/')
            ->and(ownersUpsertOwner('tools')->depth)->toBe(1);
    });

    it('refuses a parent that comes later in the batch, or whose own record failed, as if it did not exist', function (): void {
        $result = ownersUpsert([
            ownersUpsertRecord('early', ['type' => 'series', 'parent' => 'acme']),
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('broken', ['flags' => ['nope']]),
            ownersUpsertRecord('orphan', ['type' => 'series', 'parent' => 'broken']),
        ]);

        expect($result->created)->toBe(1)
            ->and(ownersUpsertFailures($result))->toBe([0 => ['early', ['parent']], 2 => ['broken', ['flags.0']], 3 => ['orphan', ['parent']]])
            ->and($result->failures[0]['errors']['parent'])->toBe(['The selected parent is invalid.'])
            ->and($result->failures[2]['errors']['parent'])->toBe(['The selected parent is invalid.'])
            ->and(ownersUpsertCodes())->toBe(['acme']);
    });

    it('refuses a parent whose create failed in the database, as if it did not exist', function (): void {
        ownersUpsertFailWritesOf(['acme']);

        $result = ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
        ]);

        expect(ownersUpsertFailures($result))->toBe([0 => ['acme', ['record']], 1 => ['tools', ['parent']]])
            ->and($result->failures[1]['errors']['parent'])->toBe(['The selected parent is invalid.'])
            ->and(ownersUpsertCodes())->toBe([]);
    });
});

describe('placement', function (): void {
    beforeEach(function (): void {
        ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
            ownersUpsertRecord('pro', ['type' => 'vendor', 'parent' => 'acme']),
        ]);
    });

    it('refuses a record that breaks a placement rule with the single actions\' message, and writes the rest', function (array $record, string $message): void {
        $before = ownersUpsertRows();

        $result = ownersUpsert([$record, ownersUpsertRecord('globex')]);

        expect(ownersUpsertFailures($result))->toBe([0 => [$record['code'], ['parent']]])
            ->and($result->failures[0]['errors']['parent'])->toBe([$message])
            ->and($result->created)->toBe(1)
            ->and(array_values(array_filter(ownersUpsertRows(), fn (array $row): bool => $row['code'] !== 'globex')))->toBe($before);
    })->with([
        'a root of a type that cannot be one' => [['code' => 'lonely', 'type' => 'series'], 'A series needs a parent owner.'],
        'a parent of a type not allowed' => [['code' => 'mini', 'type' => 'series', 'parent' => 'tools'], 'A series cannot sit under a series. Allowed parents: vendor.'],
        'a move to the root of a type that cannot be one' => [['code' => 'tools', 'parent' => null], 'A series needs a parent owner.'],
        'a move under the owner itself' => [['code' => 'acme', 'parent' => 'acme'], 'Owner "acme" cannot move under itself or its own descendant "acme".'],
        'a move under a descendant' => [['code' => 'acme', 'parent' => 'pro'], 'Owner "acme" cannot move under itself or its own descendant "pro".'],
    ]);
});

describe('path safety', function (): void {
    beforeEach(function (): void {
        ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('globex'),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
            ownersUpsertRecord('pro', ['parent' => 'acme']),
            ownersUpsertRecord('pro-max', ['parent' => 'pro']),
        ]);
    });

    it('refuses the second of two records that would swap two owners under each other', function (): void {
        $result = ownersUpsert([
            ['code' => 'acme', 'parent' => 'globex'],
            ['code' => 'globex', 'parent' => 'acme'],
        ]);

        expect($result->updated)->toBe(1)
            ->and(ownersUpsertFailures($result))->toBe([1 => ['globex', ['parent']]])
            ->and($result->failures[0]['errors']['parent'])->toBe(['Owner "globex" cannot move under itself or its own descendant "acme".'])
            ->and(ownersUpsertPath('acme'))->toBe('/globex/acme/')
            ->and(ownersUpsertPath('globex'))->toBe('/globex/');
    });

    it('places later records under the new path of an owner moved earlier, and of its descendants', function (): void {
        $result = ownersUpsert([
            ['code' => 'pro', 'parent' => 'globex'],
            ownersUpsertRecord('pro-tools', ['type' => 'series', 'parent' => 'pro']),
            ownersUpsertRecord('max-tools', ['type' => 'series', 'parent' => 'pro-max']),
            // pro-max now sits under globex: a move under it is a move under a descendant of globex.
            ['code' => 'globex', 'parent' => 'pro-max'],
        ]);

        expect([$result->updated, $result->created])->toBe([1, 2])
            ->and(ownersUpsertFailures($result))->toBe([3 => ['globex', ['parent']]])
            ->and(ownersUpsertPath('pro'))->toBe('/globex/pro/')
            ->and(ownersUpsertPath('pro-max'))->toBe('/globex/pro/pro-max/')
            ->and(ownersUpsertPath('pro-tools'))->toBe('/globex/pro/pro-tools/')
            ->and(ownersUpsertPath('max-tools'))->toBe('/globex/pro/pro-max/max-tools/')
            ->and(ownersUpsertOwner('max-tools')->depth)->toBe(3);
    });

    it('keeps the stored path of an owner whose move rolled back for later records', function (): void {
        // The move is written, then the owner's transaction fails.
        Event::listen(OwnerSavedEvent::class, function (OwnerSavedEvent $event): void {
            if ($event->owner->code === 'pro' && $event->owner->wasChanged('path')) {
                DB::table('owners_upsert_no_such_table')->insert(['id' => 1]);
            }
        });

        $result = ownersUpsert([
            ['code' => 'pro', 'parent' => 'globex', 'labels' => ['en' => 'Moved']],
            ownersUpsertRecord('pro-tools', ['type' => 'series', 'parent' => 'pro']),
            ownersUpsertRecord('max-tools', ['type' => 'series', 'parent' => 'pro-max']),
            // Still under acme, so this is a move under its own descendant.
            ['code' => 'acme', 'parent' => 'pro'],
        ]);

        expect(ownersUpsertFailures($result))->toBe([0 => ['pro', ['record']], 3 => ['acme', ['parent']]])
            ->and($result->created)->toBe(2)
            ->and(ownersUpsertOwner('pro')->labels)->toBe(['en' => 'Pro'])
            ->and(ownersUpsertPath('pro'))->toBe('/acme/pro/')
            ->and(ownersUpsertPath('pro-tools'))->toBe('/acme/pro/pro-tools/')
            ->and(ownersUpsertOwner('pro-tools')->depth)->toBe(2)
            ->and(ownersUpsertPath('max-tools'))->toBe('/acme/pro/pro-max/max-tools/');
    });
});

describe('unchanged records', function (): void {
    it('writes no owner row, fires no owner write event and queues no reindex for a record equal to the stored owner', function (): void {
        ownersUpsert([
            ownersUpsertRecord('acme', ['flags' => ['restricted', 'pricing_only']]),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
        ]);

        $before = ownersUpsertRows();
        $this->travel(5)->minutes();

        Queue::fake([SyncProductIndex::class, QueueProductSyncs::class]);
        Event::fake([OwnerCreatingActionEvent::class, OwnerCreatedActionEvent::class, OwnerUpdatingActionEvent::class, OwnerUpdatedActionEvent::class, OwnerSavedEvent::class]);

        $result = ownersUpsert([
            // Flags in another order.
            ownersUpsertRecord('acme', ['flags' => ['pricing_only', 'restricted']]),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
        ]);

        expect([$result->created, $result->updated, $result->unchanged, $result->failed()])->toBe([0, 0, 2, 0])
            ->and(ownersUpsertRows())->toBe($before);

        Queue::assertNothingPushed();
        Event::assertNotDispatched(OwnerUpdatingActionEvent::class);
        Event::assertNotDispatched(OwnerUpdatedActionEvent::class);
        Event::assertNotDispatched(OwnerSavedEvent::class);
    });

    it('stamps the external ids of unchanged records synced in one update after the writes, never a skipped or failed record\'s', function (): void {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $nine = '2026-11-10T09:00:00+00:00';

        ownersUpsert([
            ownersUpsertRecord('acme', ['external_ids' => ['erp' => 'V-acme', 'shop' => 'S-acme']]),
            ownersUpsertRecord('globex'),
            ownersUpsertRecord('initech'),
            ownersUpsertRecord('kept'),
        ]);

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        $ten = '2026-11-10T10:00:00+00:00';

        $result = null;
        $writes = ownersUpsertWritesDuring(function () use (&$result): void {
            $result = ownersUpsert([
                ownersUpsertRecord('acme', ['external_ids' => ['erp' => 'V-acme', 'shop' => 'S-acme']]),
                // Its ids not sent: still confirmed by the sync.
                ['code' => 'globex'],
                // Refused.
                ownersUpsertRecord('initech', ['flags' => ['nope']]),
            ]);
        });

        expect([$result->unchanged, $result->updated, $result->failed()])->toBe([2, 0, 1])
            ->and($writes)->toHaveCount(1)
            ->and($writes[0])->toStartWith('update "'.Tables::name('external_ids').'"')
            ->and(ownersUpsertStamps('acme'))->toBe(['erp' => [$ten, $nine], 'shop' => [$ten, $nine]])
            ->and(ownersUpsertStamps('globex'))->toBe(['erp' => [$ten, $nine]])
            ->and(ownersUpsertStamps('initech'))->toBe(['erp' => [$nine, $nine]])
            ->and(ownersUpsertStamps('kept'))->toBe(['erp' => [$nine, $nine]]);

        // Matched but skipped by the mode: not stamped either.
        $this->travelTo(Carbon::parse('2026-11-10 11:00:00'));
        ownersUpsert([ownersUpsertRecord('kept')], ['mode' => 'create']);

        expect(ownersUpsertStamps('kept'))->toBe(['erp' => [$nine, $nine]]);
    });

    it('updates a record when one sent field changes', function (Closure $change): void {
        ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('globex'),
            ownersUpsertRecord('initech', ['parent' => 'acme']),
        ]);

        $result = ownersUpsert([$change(ownersUpsertRecord('initech', ['parent' => 'acme']))]);

        expect([$result->unchanged, $result->updated, $result->failed()])->toBe([0, 1, 0]);
    })->with([
        'the labels' => [fn (array $record): array => [...$record, 'labels' => ['en' => 'Initech', 'fr' => 'Initech SA']]],
        'the labels cleared' => [fn (array $record): array => [...$record, 'labels' => null]],
        'the parent' => [fn (array $record): array => [...$record, 'parent' => 'globex']],
        'the parent to the root' => [fn (array $record): array => [...$record, 'parent' => null]],
        'an external id' => [fn (array $record): array => [...$record, 'external_ids' => ['erp' => 'V-initech', 'shop' => 'S-1']]],
        'an external id removed' => [fn (array $record): array => [...$record, 'external_ids' => ['erp' => null]]],
        'the flags' => [fn (array $record): array => [...$record, 'flags' => ['restricted', 'pricing_only']]],
    ]);
});

describe('slugs', function (): void {
    it('claims slugs in record order, and keeps an updated owner\'s slug', function (): void {
        ownersUpsert([ownersUpsertRecord('acme', ['labels' => ['en' => 'Classic']])]);

        $result = ownersUpsert([
            ownersUpsertRecord('globex', ['labels' => ['en' => 'Modern']]),
            ownersUpsertRecord('initech', ['labels' => ['en' => 'Modern']]),
            ownersUpsertRecord('acme', ['labels' => ['en' => 'Renamed']]),
            ownersUpsertRecord('hooli', ['labels' => ['en' => 'Classic']]),
        ]);

        expect([$result->created, $result->updated])->toBe([3, 1])
            ->and(OwnerModel::query()->orderBy('code')->pluck('slug', 'code')->all())->toBe([
                'acme' => 'classic',
                'globex' => 'modern',
                'hooli' => 'classic-2',
                'initech' => 'modern-2',
            ]);
    });
});

describe('the assign ability', function (): void {
    it('refuses a record whose flags are set or changed without assign, and writes its neighbours', function (): void {
        ownersUpsert([ownersUpsertRecord('acme'), ownersUpsertRecord('kept')]);

        ownersUpsertSignInWithFlagPolicy(ReadOnlyFlagPolicy::class);

        $result = ownersUpsert([
            ownersUpsertRecord('globex'),
            ownersUpsertRecord('initech', ['flags' => []]),
            ownersUpsertRecord('acme', ['flags' => ['pricing_only']]),
            // The same set sent back needs no assign.
            ownersUpsertRecord('kept', ['labels' => ['en' => 'Kept Inc']]),
        ]);

        expect(ownersUpsertFailures($result))->toBe([0 => ['globex', ['flags']], 2 => ['acme', ['flags']]])
            ->and($result->failures[0]['errors']['flags'])->toBe(['You may not change the flags of owner "globex".'])
            ->and([$result->created, $result->updated])->toBe([1, 1])
            ->and(ownersUpsertCodes())->toBe(['acme', 'initech', 'kept'])
            ->and(ownersUpsertFlags('acme'))->toBe(['restricted'])
            ->and(ownersUpsertOwner('kept')->labels)->toBe(['en' => 'Kept Inc']);
    });

    it('lets a user the policy allows set flags in a batch', function (): void {
        ownersUpsertSignInWithFlagPolicy(FlagPolicy::class);

        expect(ownersUpsert([ownersUpsertRecord('acme', ['flags' => ['pricing_only']])])->created)->toBe(1)
            ->and(ownersUpsertFlags('acme'))->toBe(['pricing_only']);
    });
});

describe('events', function (): void {
    it('announces the batch with its input and its result', function (): void {
        Event::fake([OwnersUpsertingActionEvent::class, OwnersUpsertedActionEvent::class]);

        ownersUpsert([ownersUpsertRecord('acme')], ['match' => 'erp']);

        Event::assertDispatched(OwnersUpsertingActionEvent::class, fn (OwnersUpsertingActionEvent $event): bool => count($event->data->records) === 1 && $event->data->match === 'erp');
        Event::assertDispatched(OwnersUpsertedActionEvent::class, fn (OwnersUpsertedActionEvent $event): bool => $event->result->created === 1);
    });

    it('fires each written owner\'s create or update events in record order, and no finished event for a refused one', function (): void {
        ownersUpsert([ownersUpsertRecord('acme'), ownersUpsertRecord('kept')]);

        $seen = ownersUpsertRecordEvents();

        ownersUpsert([
            ownersUpsertRecord('globex'),
            ownersUpsertRecord('acme', ['labels' => ['en' => 'Acme Inc']]),
            // Unchanged: nothing.
            ownersUpsertRecord('kept'),
            // Invalid: refused before any event.
            ownersUpsertRecord('invalid', ['type' => 'nope']),
            // Refused by a placement rule: started, never finished.
            ['code' => 'lonely', 'type' => 'series'],
        ]);

        expect($seen->getArrayCopy())->toBe([
            'OwnersUpserting',
            'OwnerCreating:globex',
            'OwnerCreated:globex',
            'OwnerUpdating:acme',
            'OwnerUpdated:acme',
            'OwnerCreating:lonely',
            'OwnersUpserted',
        ]);
    });
});

describe('the index', function (): void {
    beforeEach(function (): void {
        ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('globex'),
            ownersUpsertRecord('tools', ['type' => 'series', 'parent' => 'acme']),
            ownersUpsertRecord('widgets', ['type' => 'series', 'parent' => 'globex']),
            ownersUpsertRecord('quiet', ['type' => 'series', 'parent' => 'globex']),
        ]);

        foreach (['tools' => ['T-1', 'T-2'], 'widgets' => ['W-1'], 'quiet' => ['Q-1'], 'acme' => ['A-1']] as $code => $identifiers) {
            foreach ($identifiers as $identifier) {
                ProductModel::factory()->create(['identifier' => $identifier, 'owner_id' => ownersUpsertOwner($code)->id]);
            }
        }

        Queue::fake([SyncProductIndex::class, QueueProductSyncs::class]);
    });

    it('queues one sync for the batch, covering the products beneath every moved or flag-changed owner', function (): void {
        ownersUpsert([
            ['code' => 'tools', 'parent' => 'globex'],
            ['code' => 'widgets', 'flags' => ['pricing_only']],
            // Labels only: nothing beneath shows anything new.
            ['code' => 'quiet', 'labels' => ['en' => 'Quieter']],
            ownersUpsertRecord('new-vendor'),
        ]);

        $expected = ownersUpsertProductIds(['T-1', 'T-2', 'W-1']);

        Queue::assertPushed(SyncProductIndex::class, 1);
        Queue::assertPushed(SyncProductIndex::class, fn (SyncProductIndex $job): bool => collect($job->ids)->sort()->values()->all() === $expected);
        Queue::assertNotPushed(QueueProductSyncs::class);
    });

    it('queues no sync when no owner moved or changed its flags', function (): void {
        ownersUpsert([
            ['code' => 'quiet', 'labels' => ['en' => 'Quieter']],
            ['code' => 'tools', 'parent' => 'acme'],
            ownersUpsertRecord('new-vendor'),
        ]);

        Queue::assertNothingPushed();
    });

    it('still queues the owners already written when the batch throws midway', function (): void {
        Event::listen(OwnerUpdatingActionEvent::class, function (OwnerUpdatingActionEvent $event): void {
            if ($event->owner->code === 'widgets') {
                throw new RuntimeException('The batch broke.');
            }
        });

        expect(fn () => ownersUpsert([
            ['code' => 'tools', 'parent' => 'globex'],
            ['code' => 'widgets', 'flags' => ['pricing_only']],
        ]))->toThrow(RuntimeException::class, 'The batch broke.');

        $expected = ownersUpsertProductIds(['T-1', 'T-2']);

        Queue::assertPushed(SyncProductIndex::class, 1);
        Queue::assertPushed(SyncProductIndex::class, fn (SyncProductIndex $job): bool => collect($job->ids)->sort()->values()->all() === $expected);
    });

    it('neither announces nor reindexes an owner whose write rolled back', function (): void {
        ownersUpsertFailWritesOf(['tools']);
        $seen = ownersUpsertRecordEvents();

        $result = ownersUpsert([['code' => 'tools', 'parent' => 'globex', 'flags' => []]]);

        expect(ownersUpsertFailures($result))->toBe([0 => ['tools', ['record']]])
            ->and($seen->getArrayCopy())->toBe(['OwnersUpserting', 'OwnerUpdating:tools', 'OwnersUpserted'])
            ->and(ownersUpsertPath('tools'))->toBe('/acme/tools/')
            ->and(ownersUpsertFlags('tools'))->toBe(['restricted']);

        Queue::assertNothingPushed();
    });
});

describe('database errors', function (): void {
    it('rolls back only the owner whose write failed, and reports it without its SQL', function (): void {
        ownersUpsertFailWritesOf(['bad']);

        $result = ownersUpsert([
            ownersUpsertRecord('acme'),
            ownersUpsertRecord('bad', ['external_ids' => ['erp' => 'V-bad'], 'flags' => ['restricted']]),
            ownersUpsertRecord('globex'),
        ]);

        expect(ownersUpsertFailures($result))->toBe([1 => ['bad', ['record']]])
            ->and($result->failures[0]['errors']['record'])->toBe(['The record could not be written to the database.'])
            ->and(json_encode($result->failures, JSON_THROW_ON_ERROR))->not->toContain('owners_upsert_no_such_table')
            ->and($result->created)->toBe(2)
            ->and(ownersUpsertCodes())->toBe(['acme', 'globex'])
            ->and(ExternalIdModel::query()->where('external_id', 'V-bad')->exists())->toBeFalse()
            ->and(DB::table(Tables::name('flag_assignments'))->count())->toBe(2)
            ->and(ownersUpsertExternalIds('globex'))->toBe(['erp' => 'V-globex']);
    });

    it('reports a unique-index violation on the code as a code failure', function (): void {
        Event::listen(OwnerSavingEvent::class, function (OwnerSavingEvent $event): void {
            if (! $event->owner->exists && $event->owner->code === 'clash') {
                throw new UniqueConstraintViolationException('testing', 'insert into owners', [], new PDOException('UNIQUE constraint failed: owners.code'));
            }
        });

        $result = ownersUpsert([ownersUpsertRecord('clash'), ownersUpsertRecord('acme')]);

        expect(ownersUpsertFailures($result))->toBe([0 => ['clash', ['code']]])
            ->and($result->failures[0]['errors']['code'][0])->toContain('clash')
            ->and($result->failures[0]['errors']['code'][0])->not->toContain('UNIQUE')
            ->and(ownersUpsertCodes())->toBe(['acme']);
    });
});

describe('size', function (): void {
    it('reads matched owners in chunks of showroom.impex.chunk', function (string $match): void {
        $records = array_map(fn (int $i): array => ownersUpsertRecord('owner-'.$i, ['external_ids' => ['erp' => 'V-'.$i]]), range(1, 5));
        ownersUpsert($records);

        config()->set('showroom.impex.chunk', 2);

        /** @var list<QueryExecuted> $queries */
        $queries = [];
        $listening = true;
        DB::listen(function (QueryExecuted $query) use (&$queries, &$listening): void {
            if ($listening) {
                $queries[] = $query;
            }
        });

        $result = ownersUpsert($records, ['match' => $match]);
        $listening = false;

        $lookups = array_values(array_filter($queries, fn (QueryExecuted $query): bool => preg_match(
            $match === 'code'
                ? '/^select \* from "'.Tables::name('owners').'" where "code" in/'
                : '/^select "external_id", "subject_id" from "'.Tables::name('external_ids').'"/',
            $query->sql,
        ) === 1));

        expect($result->unchanged)->toBe(5)
            ->and($lookups)->toHaveCount(3)
            ->and(array_map(fn (QueryExecuted $query): int => count(array_filter($query->bindings, fn (mixed $binding): bool => is_string($binding) && str_contains($binding, '-'))), $lookups))
            ->each->toBeLessThanOrEqual(2);
    })->with(['by code' => 'code', 'by external id' => 'erp']);
});
