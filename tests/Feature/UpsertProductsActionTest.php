<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Family\Actions\CreateFamilyAction;
use RefactorCircus\Showroom\Domains\Family\Data\CreateFamilyData;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Policies\FlagPolicy;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Actions\UpsertProductsAction;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsData;
use RefactorCircus\Showroom\Domains\Product\Data\UpsertProductsResult;
use RefactorCircus\Showroom\Domains\Product\Enums\ProductStatus;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;
use RefactorCircus\Showroom\Domains\Product\Events\ProductCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductSavedEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductsUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductsUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Product\Events\ProductUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Product\Services\ProductChangeCapture;
use RefactorCircus\Showroom\Domains\Search\Events\ProductsQueuedForSync;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;
use RefactorCircus\Showroom\Jobs\SyncProductIndex;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

/*
 * UpsertProductsAction loads a batch of product records in API shape: it
 * matches each to a stored product, by identifier or by its id in a source
 * (an ERP, a shop), creates or updates it, and leaves alone what has not
 * changed, only stamping its external ids synced. A record that fails is
 * reported; its neighbours are still written.
 */

beforeEach(function (): void {
    Catalog::apparel();

    app(CreateFamilyAction::class)->execute(CreateFamilyData::from([
        'code' => 'basics',
        'attributes' => [['attribute' => 'name'], ['attribute' => 'pack_size']],
    ]));

    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    OwnerTypeModel::factory()->create(['code' => 'group', 'owns_products' => false]);

    foreach (['acme' => 'vendor', 'globex' => 'vendor', 'holding' => 'group'] as $code => $type) {
        app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => $code, 'type' => $type]));
    }

    $this->postJson(route('showroom.categories.store'), ['code' => 'master'])->assertCreated();
    $this->postJson(route('showroom.categories.store'), ['code' => 'shirts', 'parent' => 'master'])->assertCreated();
    $this->postJson(route('showroom.association-types.store'), ['code' => 'x_sell'])->assertCreated();

    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'clearance', 'applies_to' => ['product'], 'sort_order' => 2]);
});

/**
 * @param  list<mixed>  $records
 * @param  array<string, mixed>  $options
 */
function upsertProducts(array $records, array $options = []): UpsertProductsResult
{
    return app(UpsertProductsAction::class)->execute(UpsertProductsData::from(['records' => $records, ...$options]));
}

/**
 * A simple product record as an export or an ERP sends it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function upsertRecord(string $identifier, array $overrides = []): array
{
    return [
        'identifier' => $identifier,
        'family' => 'shirts',
        'owner' => 'acme',
        'enabled' => true,
        'categories' => ['shirts'],
        'values' => ['name' => Catalog::value('Shirt '.$identifier), 'pack_size' => Catalog::value(2)],
        'external_ids' => ['erp' => 'E-'.$identifier],
        'flags' => ['restricted'],
        ...$overrides,
    ];
}

/**
 * @return list<string>
 */
function upsertStoredFlags(ProductModel $product): array
{
    /** @var list<string> $codes */
    $codes = DB::table(Tables::name('flag_assignments'))
        ->join(Tables::name('flags'), Tables::name('flags').'.id', '=', Tables::name('flag_assignments').'.flag_id')
        ->where('subject_type', $product->getMorphClass())
        ->where('subject_id', $product->id)
        ->orderBy('code')
        ->pluck('code')
        ->all();

    return $codes;
}

/**
 * The failures of a result, as index => [identifier, error keys].
 *
 * @return array<int, array{0: string|null, 1: list<string>}>
 */
function upsertFailures(UpsertProductsResult $result): array
{
    $failures = [];

    foreach ($result->failures as $failure) {
        $failures[$failure['index']] = [$failure['identifier'], array_keys($failure['errors'])];
    }

    return $failures;
}

/**
 * @param  class-string<FlagPolicy>  $policy
 */
function upsertSignInWithFlagPolicy(string $policy): void
{
    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, $policy);

    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));

    test()->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));
}

function upsertCaseInsensitiveColumn(string $table, string $column): void
{
    Schema::table(Tables::name($table), function (Blueprint $blueprint) use ($column): void {
        $blueprint->string($column, 191)->collation('NOCASE')->change();
    });
}

function upsertProduct(string $identifier): ProductModel
{
    return ProductModel::query()->where('identifier', $identifier)->sole();
}

/**
 * The identifier of the product holding an id in a source, if any.
 */
function upsertExternalIdHolder(string $source, string $externalId): ?string
{
    $subjectId = ExternalIdModel::query()
        ->where('subject_type', (new ProductModel)->getMorphClass())
        ->where('source', $source)
        ->where('external_id', $externalId)
        ->value('subject_id');

    return $subjectId === null ? null : ProductModel::query()->whereKey($subjectId)->value('identifier');
}

/**
 * A product's external id rows, as {source: [synced_at, updated_at]} in ISO-8601.
 *
 * @return array<string, array{0: string|null, 1: string|null}>
 */
function upsertExternalIdStamps(string $identifier): array
{
    return ExternalIdModel::query()
        ->where('subject_type', upsertProduct($identifier)->getMorphClass())
        ->where('subject_id', upsertProduct($identifier)->id)
        ->orderBy('source')
        ->get()
        ->mapWithKeys(fn (ExternalIdModel $row): array => [$row->source => [$row->synced_at?->toIso8601String(), $row->updated_at?->toIso8601String()]])
        ->all();
}

/**
 * The statements that wrote (insert, update, delete) while running a callback.
 *
 * @return list<string>
 */
function upsertWritesDuring(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $writes = array_values(array_filter(
        array_column(DB::getQueryLog(), 'query'),
        fn (string $sql): bool => preg_match('/^\s*(insert|update|delete)\b/i', $sql) === 1,
    ));

    DB::disableQueryLog();

    return $writes;
}

describe('modes and matching', function (): void {
    it('creates new records and updates matched ones by identifier', function (): void {
        upsertProducts([upsertRecord('TEE')]);

        $result = upsertProducts([
            upsertRecord('TEE', ['values' => ['name' => Catalog::value('Renamed')]]),
            upsertRecord('CAP'),
        ]);

        expect($result->created)->toBe(1)
            ->and($result->updated)->toBe(1)
            ->and($result->unchanged)->toBe(0)
            ->and($result->failed())->toBe(0)
            ->and(ProductModel::query()->orderBy('identifier')->pluck('identifier')->all())->toBe(['CAP', 'TEE'])
            ->and(upsertProduct('TEE')->ownValues()['name'])->toBe(['<all_channels>' => ['<all_locales>' => 'Renamed']])
            ->and(upsertProduct('CAP')->slug)->toBe('cap');
    });

    it('skips matched records in create mode and new records in update mode', function (): void {
        upsertProducts([upsertRecord('TEE')]);

        $create = upsertProducts([upsertRecord('TEE', ['enabled' => false]), upsertRecord('CAP')], ['mode' => 'create']);
        $update = upsertProducts([upsertRecord('TEE', ['enabled' => false]), upsertRecord('MUG')], ['mode' => UpsertMode::Update->value]);

        expect([$create->created, $create->updated, $create->skipped])->toBe([1, 0, 1])
            ->and([$update->created, $update->updated, $update->skipped])->toBe([0, 1, 1])
            ->and(ProductModel::query()->orderBy('identifier')->pluck('identifier')->all())->toBe(['CAP', 'TEE'])
            ->and(upsertProduct('TEE')->enabled)->toBeFalse();
    });

    it('matches by a source\'s external id', function (): void {
        upsertProducts([upsertRecord('OLD', ['external_ids' => ['erp' => 'E-1']])]);

        $result = upsertProducts([
            upsertRecord('OLD', ['external_ids' => ['erp' => 'E-1'], 'enabled' => false]),
            upsertRecord('NEW', ['external_ids' => ['erp' => 'E-2']]),
        ], ['match' => 'erp']);

        expect([$result->created, $result->updated])->toBe([1, 1])
            ->and(upsertProduct('OLD')->enabled)->toBeFalse()
            ->and(ProductModel::query()->count())->toBe(2);
    });

    it('refuses to change the identifier of a product matched by an external id', function (): void {
        upsertProducts([upsertRecord('OLD', ['external_ids' => ['erp' => 'E-1']])]);

        $result = upsertProducts([upsertRecord('RENAMED', ['external_ids' => ['erp' => 'E-1']])], ['match' => 'erp']);

        expect(upsertFailures($result))->toBe([0 => ['RENAMED', ['identifier']]])
            ->and(ProductModel::query()->pluck('identifier')->all())->toBe(['OLD']);
    });

    it('matches an identifier or an external id that differs only by case', function (): void {
        upsertCaseInsensitiveColumn('products', 'identifier');
        upsertCaseInsensitiveColumn('external_ids', 'external_id');

        upsertProducts([upsertRecord('TEE', ['external_ids' => ['erp' => 'ABC-1']])]);

        $byIdentifier = upsertProducts([upsertRecord('tee', ['external_ids' => ['erp' => 'ABC-1'], 'enabled' => false])]);
        $bySource = upsertProducts([upsertRecord('Tee', ['external_ids' => ['erp' => 'abc-1']])], ['match' => 'erp']);

        expect([$byIdentifier->created, $byIdentifier->updated, $byIdentifier->failed()])->toBe([0, 1, 0])
            ->and([$bySource->created, $bySource->updated, $bySource->failed()])->toBe([0, 1, 0])
            ->and(ProductModel::query()->pluck('identifier')->all())->toBe(['TEE']);
    });

    it('keeps the first of two records sharing a (source, id) that differs only by case', function (): void {
        upsertCaseInsensitiveColumn('external_ids', 'external_id');

        $result = upsertProducts([
            upsertRecord('TEE', ['external_ids' => ['erp' => 'ABC-1']]),
            upsertRecord('CAP', ['external_ids' => ['erp' => 'abc-1']]),
        ]);

        expect($result->created)->toBe(1)
            ->and(upsertFailures($result))->toBe([1 => ['CAP', ['external_ids.erp']]])
            ->and(ProductModel::query()->pluck('identifier')->all())->toBe(['TEE']);
    });

    it('keeps the first record holding a (source, id) in batch order, whichever way it is written', function (string $identifier, array $overrides): void {
        upsertProducts([upsertRecord('TEE')]);

        $result = upsertProducts([
            upsertRecord($identifier, ['external_ids' => ['erp' => 'E-1'], ...$overrides]),
            // A new simple product, written in bulk before the others.
            upsertRecord('CAP', ['external_ids' => ['erp' => 'E-1']]),
        ]);

        expect(upsertFailures($result))->toBe([1 => ['CAP', ['external_ids.erp']]])
            ->and(upsertExternalIdHolder('erp', 'E-1'))->toBe($identifier)
            ->and(ProductModel::query()->where('identifier', 'CAP')->exists())->toBeFalse();
    })->with([
        'a new product written on its own' => ['BAG', ['associations' => ['x_sell' => ['products' => ['TEE']]]]],
        'an update' => ['TEE', []],
    ]);

    it('lets a new record take a (source, id) that an update earlier in the batch removes', function (): void {
        upsertProducts([upsertRecord('TEE')]);

        $result = upsertProducts([
            upsertRecord('TEE', ['external_ids' => ['erp' => null]]),
            upsertRecord('CAP', ['external_ids' => ['erp' => 'E-TEE']]),
        ]);

        expect([$result->created, $result->updated, $result->failed()])->toBe([1, 1, 0])
            ->and(upsertExternalIdHolder('erp', 'E-TEE'))->toBe('CAP');
    });

    it('refuses a new record a (source, id) that an update later in the batch removes', function (): void {
        upsertProducts([upsertRecord('TEE')]);

        $result = upsertProducts([
            upsertRecord('CAP', ['external_ids' => ['erp' => 'E-TEE']]),
            upsertRecord('TEE', ['external_ids' => ['erp' => null]]),
        ]);

        expect(upsertFailures($result))->toBe([0 => ['CAP', ['external_ids.erp']]])
            ->and($result->updated)->toBe(1)
            ->and(upsertExternalIdHolder('erp', 'E-TEE'))->toBeNull();
    });

    it('refuses the second of two new records with the same identifier', function (): void {
        $result = upsertProducts([upsertRecord('TEE'), upsertRecord('TEE', ['external_ids' => ['erp' => 'E-2']])]);

        expect($result->created)->toBe(1)
            ->and(upsertFailures($result))->toBe([1 => ['TEE', ['identifier']]]);
    });

    it('refuses an unknown mode or a match that is not a source name', function (array $options, string $key): void {
        expect(fn () => upsertProducts([upsertRecord('TEE')], $options))
            ->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey($key));
    })->with([
        'mode' => [['mode' => 'replace'], 'mode'],
        'match' => [['match' => 'ERP System'], 'match'],
    ]);
});

describe('validation', function (): void {
    it('reports each failing record by index, identifier and errors, and writes the rest', function (): void {
        $result = upsertProducts([
            upsertRecord('TEE'),
            upsertRecord('BAD', ['family' => 'nope']),
            'not an object',
            upsertRecord('', ['identifier' => null]),
            upsertRecord('CAP'),
        ]);

        expect($result->created)->toBe(2)
            ->and($result->failed())->toBe(3)
            ->and(upsertFailures($result))->toBe([
                1 => ['BAD', ['family']],
                2 => [null, ['record']],
                3 => [null, ['identifier']],
            ])
            ->and($result->failures[0]['errors']['family'][0])->toBeString()
            ->and(ProductModel::query()->orderBy('identifier')->pluck('identifier')->all())->toBe(['CAP', 'TEE']);
    });

    it('refuses read-only keys sent with a record', function (): void {
        upsertProducts([upsertRecord('TEE')]);

        $result = upsertProducts([
            upsertRecord('TEE', ['slug' => 'tee']),
            upsertRecord('CAP', ['effective_flags' => []]),
        ]);

        expect(upsertFailures($result))->toBe([0 => ['TEE', ['slug']], 1 => ['CAP', ['effective_flags']]]);
    });

    it('reports a record refused by a domain guard, and writes the rest', function (): void {
        $result = upsertProducts([upsertRecord('TEE', ['owner' => 'holding']), upsertRecord('CAP')]);

        expect($result->created)->toBe(1)
            ->and(upsertFailures($result))->toBe([0 => ['TEE', ['owner']]]);
    });

    it('reports a record whose write fails in the database, and writes the rest', function (): void {
        upsertProducts([upsertRecord('TEE'), upsertRecord('KEEP')]);

        Event::listen(ProductSavedEvent::class, function (ProductSavedEvent $event): void {
            if ($event->product->identifier === 'BAG') {
                throw new UniqueConstraintViolationException('testing', 'insert into products', [], new PDOException('UNIQUE constraint failed'));
            }

            if ($event->product->identifier === 'TEE') {
                DB::table('upsert_no_such_table')->insert(['id' => 1]);
            }
        });

        $result = upsertProducts([
            upsertRecord('TEE', ['enabled' => false]),
            upsertRecord('BAG', ['associations' => ['x_sell' => ['products' => ['KEEP']]]]),
            upsertRecord('CAP'),
            upsertRecord('KEEP', ['enabled' => false]),
        ]);

        expect(upsertFailures($result))->toBe([0 => ['TEE', ['record']], 1 => ['BAG', ['record']]])
            ->and([$result->created, $result->updated])->toBe([1, 1])
            ->and(json_encode($result->failures, JSON_THROW_ON_ERROR))->not->toContain('upsert_no_such_table')
            ->and(upsertProduct('TEE')->enabled)->toBeTrue()
            ->and(upsertProduct('KEEP')->enabled)->toBeFalse()
            ->and(ProductModel::query()->where('identifier', 'BAG')->exists())->toBeFalse()
            ->and(ProductModel::query()->where('identifier', 'CAP')->exists())->toBeTrue();
    });
});

describe('unchanged records', function (): void {
    it('writes, records, queues and announces nothing for a record equal to the stored product', function (): void {
        upsertProducts([upsertRecord('CAP'), upsertRecord('TEE', [
            'associations' => ['x_sell' => ['products' => ['CAP']]],
        ])]);

        $before = DB::table(Tables::name('products'))->orderBy('identifier')->get()->map(fn (object $row): array => (array) $row)->all();
        $versions = VersionModel::query()->count();

        $this->travel(5)->minutes();

        Bus::fake([SyncProductIndex::class]);
        Event::fake([
            ProductCreatingActionEvent::class,
            ProductCreatedActionEvent::class,
            ProductUpdatingActionEvent::class,
            ProductUpdatedActionEvent::class,
            ProductSavedEvent::class,
        ]);

        $result = upsertProducts([
            upsertRecord('CAP'),
            // Sent as a client might: a number as a string, keys in another order.
            [...upsertRecord('TEE', ['values' => ['pack_size' => [['data' => '2', 'scope' => null, 'locale' => null]], 'name' => Catalog::value('Shirt TEE')]]), 'associations' => ['x_sell' => ['products' => ['CAP']]]],
        ]);

        expect([$result->created, $result->updated, $result->unchanged, $result->failed()])->toBe([0, 0, 2, 0])
            ->and(DB::table(Tables::name('products'))->orderBy('identifier')->get()->map(fn (object $row): array => (array) $row)->all())->toBe($before)
            ->and(VersionModel::query()->count())->toBe($versions);

        Bus::assertNotDispatched(SyncProductIndex::class);
        Event::assertNotDispatched(ProductCreatingActionEvent::class);
        Event::assertNotDispatched(ProductUpdatingActionEvent::class);
        Event::assertNotDispatched(ProductSavedEvent::class);
    });

    it('stamps the external ids of unchanged records synced, in one update and no other write', function (): void {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $nine = '2026-11-10T09:00:00+00:00';

        upsertProducts([
            upsertRecord('TEE', ['external_ids' => ['erp' => 'E-TEE', 'shop' => 'S-TEE']]),
            upsertRecord('CAP'),
            upsertRecord('KEEP'),
        ]);

        $products = DB::table(Tables::name('products'))->orderBy('identifier')->get()->map(fn (object $row): array => (array) $row)->all();

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        $ten = '2026-11-10T10:00:00+00:00';

        $result = null;
        $writes = upsertWritesDuring(function () use (&$result): void {
            $result = upsertProducts([
                upsertRecord('TEE', ['external_ids' => ['erp' => 'E-TEE', 'shop' => 'S-TEE']]),
                // Matched by identifier, its ids not sent: still confirmed by the sync.
                upsertRecord('CAP', ['external_ids' => []]),
            ]);
        });

        expect([$result->unchanged, $result->updated, $result->created, $result->failed()])->toBe([2, 0, 0, 0])
            ->and($writes)->toHaveCount(1)
            ->and($writes[0])->toStartWith('update "'.Tables::name('external_ids').'"')
            // Only the stamp moves: the ids keep their updated_at.
            ->and(upsertExternalIdStamps('TEE'))->toBe(['erp' => [$ten, $nine], 'shop' => [$ten, $nine]])
            ->and(upsertExternalIdStamps('CAP'))->toBe(['erp' => [$ten, $nine]])
            // A product the batch did not send keeps its stamp.
            ->and(upsertExternalIdStamps('KEEP'))->toBe(['erp' => [$nine, $nine]])
            ->and(DB::table(Tables::name('products'))->orderBy('identifier')->get()->map(fn (object $row): array => (array) $row)->all())->toBe($products);
    });

    it('stamps the external ids an updated record sends, through the update', function (): void {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        upsertProducts([upsertRecord('TEE', ['external_ids' => ['erp' => 'E-TEE', 'shop' => 'S-TEE']])]);

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        $result = upsertProducts([upsertRecord('TEE', ['external_ids' => ['erp' => 'E-TEE'], 'enabled' => false])]);

        expect($result->updated)->toBe(1)
            ->and(upsertExternalIdStamps('TEE'))->toBe([
                'erp' => ['2026-11-10T10:00:00+00:00', '2026-11-10T09:00:00+00:00'],
                'shop' => ['2026-11-10T09:00:00+00:00', '2026-11-10T09:00:00+00:00'],
            ]);
    });

    it('updates a record when one sent field changes', function (Closure $change): void {
        upsertProducts([upsertRecord('CAP'), upsertRecord('TEE')]);

        $result = upsertProducts([$change(upsertRecord('TEE'))]);

        expect([$result->unchanged, $result->updated, $result->failed()])->toBe([0, 1, 0]);
    })->with([
        'a value' => [fn (array $record): array => [...$record, 'values' => ['name' => Catalog::value('Renamed')]]],
        'a value cleared' => [fn (array $record): array => [...$record, 'values' => ['pack_size' => Catalog::value(null)]]],
        'the family' => [fn (array $record): array => [...$record, 'family' => 'basics']],
        'the owner' => [fn (array $record): array => [...$record, 'owner' => 'globex']],
        'the categories' => [fn (array $record): array => [...$record, 'categories' => ['master', 'shirts']]],
        'an association' => [fn (array $record): array => [...$record, 'associations' => ['x_sell' => ['products' => ['CAP']]]]],
        'enabled' => [fn (array $record): array => [...$record, 'enabled' => false]],
        'an external id' => [fn (array $record): array => [...$record, 'external_ids' => ['erp' => 'E-TEE', 'shop' => 'S-1']]],
        'an external id removed' => [fn (array $record): array => [...$record, 'external_ids' => ['erp' => null]]],
        'the flags' => [fn (array $record): array => [...$record, 'flags' => ['restricted', 'clearance']]],
    ]);

    it('refuses a record that would move a variant to another parent, rather than call it unchanged', function (): void {
        $this->postJson(route('showroom.product-models.store'), ['code' => 'polo', 'family_variant' => 'shirts_by_size', 'owner' => 'acme'])->assertCreated();
        $this->postJson(route('showroom.product-models.store'), ['code' => 'polo-2', 'family_variant' => 'shirts_by_size', 'owner' => 'acme'])->assertCreated();

        $variant = ['identifier' => 'POLO-M', 'parent' => 'polo', 'values' => ['size' => Catalog::value('m')]];

        expect(upsertProducts([$variant])->created)->toBe(1)
            ->and(upsertProducts([$variant])->unchanged)->toBe(1)
            ->and(upsertFailures(upsertProducts([[...$variant, 'parent' => 'polo-2']])))->toBe([0 => ['POLO-M', ['parent']]]);
    });
});

describe('versions and the index', function (): void {
    it('records one created version, then one updated version only when something changed', function (): void {
        $actions = fn (): array => VersionModel::query()->where('versionable_id', upsertProduct('TEE')->id)->orderBy('version')->pluck('action')->all();

        upsertProducts([upsertRecord('TEE')]);
        expect($actions())->toBe(['created']);

        upsertProducts([upsertRecord('TEE')]);
        expect($actions())->toBe(['created']);

        upsertProducts([upsertRecord('TEE', ['enabled' => false])]);
        expect($actions())->toBe(['created', 'updated']);
    });

    it('sends an approved product that changes back to draft, as UpdateProductAction does', function (): void {
        upsertProducts([upsertRecord('TEE')]);
        upsertProduct('TEE')->forceFill(['status' => ProductStatus::Approved])->save();

        upsertProducts([upsertRecord('TEE')]);
        expect(upsertProduct('TEE')->status)->toBe(ProductStatus::Approved);

        upsertProducts([upsertRecord('TEE', ['enabled' => false])]);
        expect(upsertProduct('TEE')->status)->toBe(ProductStatus::Draft);
    });

    it('queues one index sync covering every product the batch wrote', function (): void {
        upsertProducts([upsertRecord('TEE'), upsertRecord('KEEP')]);

        Bus::fake([SyncProductIndex::class]);

        upsertProducts([
            upsertRecord('TEE', ['enabled' => false]),
            upsertRecord('KEEP'),
            upsertRecord('CAP'),
            upsertRecord('MUG'),
            // Associations take the per-record path.
            upsertRecord('BAG', ['associations' => ['x_sell' => ['products' => ['CAP']]]]),
        ]);

        $written = ProductModel::query()->whereIn('identifier', ['TEE', 'CAP', 'MUG', 'BAG'])->pluck('id')->sort()->values()->all();

        Bus::assertDispatchedTimes(SyncProductIndex::class, 1);
        Bus::assertDispatched(SyncProductIndex::class, fn (SyncProductIndex $job): bool => collect($job->ids)->sort()->values()->all() === $written);
    });
});

describe('the assign ability', function (): void {
    it('refuses a record whose flags change without assign, and writes the rest', function (): void {
        upsertProducts([upsertRecord('TEE'), upsertRecord('KEEP')]);

        upsertSignInWithFlagPolicy(ReadOnlyFlagPolicy::class);

        $result = upsertProducts([
            upsertRecord('CAP'),
            upsertRecord('MUG', ['flags' => []]),
            upsertRecord('TEE', ['flags' => ['clearance']]),
            upsertRecord('KEEP', ['enabled' => false]),
        ]);

        expect(upsertFailures($result))->toBe([0 => ['CAP', ['flags']], 2 => ['TEE', ['flags']]])
            ->and([$result->created, $result->updated])->toBe([1, 1])
            ->and(ProductModel::query()->where('identifier', 'CAP')->exists())->toBeFalse()
            ->and(upsertStoredFlags(upsertProduct('TEE')))->toBe(['restricted'])
            ->and(upsertProduct('KEEP')->enabled)->toBeFalse();
    });

    it('lets a user the policy allows change flags in a batch', function (): void {
        upsertSignInWithFlagPolicy(FlagPolicy::class);

        expect(upsertProducts([upsertRecord('TEE', ['flags' => ['clearance']])])->created)->toBe(1)
            ->and(upsertStoredFlags(upsertProduct('TEE')))->toBe(['clearance']);
    });
});

describe('events and change capture', function (): void {
    it('announces the batch with its input and its result', function (): void {
        Event::fake([ProductsUpsertingActionEvent::class, ProductsUpsertedActionEvent::class]);

        upsertProducts([upsertRecord('TEE')], ['capture_changes' => false]);

        Event::assertDispatched(ProductsUpsertingActionEvent::class, fn (ProductsUpsertingActionEvent $event): bool => count($event->data->records) === 1 && ! $event->data->captureChanges);
        Event::assertDispatched(ProductsUpsertedActionEvent::class, fn (ProductsUpsertedActionEvent $event): bool => $event->result->created === 1);
    });

    it('writes with change capture suppressed only when asked', function (array $options, bool $suppressed): void {
        $seen = [];

        Event::listen(ProductsQueuedForSync::class, function () use (&$seen): void {
            $seen[] = app(ProductChangeCapture::class)->isSuppressed();
        });

        upsertProducts([upsertRecord('TEE'), upsertRecord('BAG', ['associations' => ['x_sell' => ['products' => ['TEE']]]])], $options);

        expect($seen)->toBe([$suppressed])
            ->and(app(ProductChangeCapture::class)->isSuppressed())->toBeFalse();
    })->with([
        'by default' => [[], false],
        'with capture_changes false' => [['capture_changes' => false], true],
    ]);
});
