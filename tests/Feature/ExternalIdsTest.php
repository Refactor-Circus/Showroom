<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ExecuteTools;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ListOwnersTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ShowOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\DeleteProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ShowProductTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Services\ProductDocument;
use RefactorCircus\Showroom\Mcp\ShowroomServer;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\RecordingScoutEngine;

/*
 * Products and owners carry their ids in other systems (an ERP, a shop) as a
 * {source: id} map, unique per subject type and source, and one id per record
 * and source, so a later sync finds the record it wrote before. Each id keeps
 * when its source last synced it, as Roster's organization links do.
 */

/**
 * @param  array<string, mixed>  $input
 */
function externalProduct(string $identifier, array $input = []): ProductModel
{
    return app(CreateProductAction::class)->execute(CreateProductData::from(['identifier' => $identifier, ...$input]));
}

/**
 * @param  array<string, mixed>  $input
 */
function externalOwner(string $code, array $input = []): OwnerModel
{
    if (! OwnerTypeModel::query()->where('code', 'vendor')->exists()) {
        OwnerTypeModel::factory()->create(['code' => 'vendor']);
    }

    return app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => $code, 'type' => 'vendor', ...$input]));
}

/**
 * The external ids stored for a record, as {source: id}.
 *
 * @return array<string, string>
 */
function storedExternalIds(ProductModel|OwnerModel $subject): array
{
    return ExternalIdModel::query()
        ->where('subject_type', $subject->getMorphClass())
        ->where('subject_id', $subject->getKey())
        ->orderBy('source')
        ->pluck('external_id', 'source')
        ->all();
}

/**
 * When each of a record's external ids last synced, as {source: ISO-8601|null}.
 *
 * @return array<string, string|null>
 */
function storedSyncedAt(ProductModel|OwnerModel $subject): array
{
    return ExternalIdModel::query()
        ->where('subject_type', $subject->getMorphClass())
        ->where('subject_id', $subject->getKey())
        ->orderBy('source')
        ->get()
        ->mapWithKeys(fn (ExternalIdModel $row): array => [$row->source => $row->synced_at?->toIso8601String()])
        ->all();
}

/**
 * Insert an external id row directly, bypassing the ExternalIds service.
 *
 * @param  array<string, mixed>  $attributes
 */
function insertExternalIdRow(ProductModel|OwnerModel $subject, string $source, string $externalId, array $attributes = []): void
{
    DB::table(Tables::name('external_ids'))->insert([
        'id' => (string) Str::ulid(),
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => $subject->getKey(),
        'source' => $source,
        'external_id' => $externalId,
        ...$attributes,
    ]);
}

/**
 * The validation errors a callback throws, by key.
 *
 * @return array<string, array<int, string>>
 */
function externalIdErrors(Closure $callback): array
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    throw new RuntimeException('Expected a validation error.');
}

/**
 * The JSON text an MCP tool call answers with through the server's
 * execute_tools, before any decoding.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function rawMcpOutput(string $tool, array $arguments): string
{
    $execute = (new ShowroomServer(new FakeTransporter))->createContext()->tools()
        ->first(fn (mixed $candidate): bool => $candidate instanceof ExecuteTools);

    expect($execute)->toBeInstanceOf(ExecuteTools::class);

    $envelope = $execute->handle(new McpRequest([
        'calls' => [['name' => app($tool)->name(), 'arguments' => $arguments]],
    ]));

    return (string) ($envelope[0] ?? null)?->content();
}

/**
 * Make a column compare case-insensitively, as the default MySQL collation
 * does, so the database-backed checks do not depend on SQLite's BINARY.
 */
function caseInsensitiveColumn(string $table, string $column): void
{
    Schema::table(Tables::name($table), function (Blueprint $blueprint) use ($column): void {
        $blueprint->string($column, 191)->collation('NOCASE')->change();
    });
}

describe('writing the map', function (): void {
    it('stores the map a product and an owner are created with', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1', 'shop' => 'S-1']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'V-1']]);

        expect(storedExternalIds($product))->toBe(['erp' => 'E-1', 'shop' => 'S-1'])
            ->and(storedExternalIds($owner))->toBe(['erp' => 'V-1'])
            ->and(ExternalIdModel::query()->where('subject_id', $product->id)->value('subject_type'))->toBe($product->getMorphClass());
    });

    it('changes only the sources an update sends, and removes a source sent as null', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1', 'shop' => 'S-1', 'pim' => 'P-1']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'V-1', 'shop' => 'VS-1']]);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['external_ids' => ['shop' => 'S-2', 'pim' => null, 'd365' => 'D-1']]));
        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['external_ids' => ['erp' => null]]));

        expect(storedExternalIds($product))->toBe(['d365' => 'D-1', 'erp' => 'E-1', 'shop' => 'S-2'])
            ->and(storedExternalIds($owner))->toBe(['shop' => 'VS-1']);
    });

    it('leaves the map alone when an update does not send it', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['enabled' => false]));

        expect(storedExternalIds($product))->toBe(['erp' => 'E-1']);
    });

    it('accepts source names and ids within the rules', function (array $map): void {
        $product = externalProduct('TEE', ['external_ids' => $map]);

        expect(storedExternalIds($product))->toBe($map);
    })->with([
        'one character' => [['a' => 'x']],
        'digits, dashes and underscores' => [['d365-fo_2' => 'x']],
        '50 characters' => [[str_repeat('s', 50) => 'x']],
        'a 191-character id' => [['erp' => str_repeat('i', 191)]],
        'an id with spaces, quotes and a slash' => [['erp' => '83AL-IC/40 "4" RED']],
    ]);

    it('refuses a source name or an id outside the rules', function (array $map, string $key): void {
        $errors = externalIdErrors(fn () => externalProduct('TEE', ['external_ids' => $map]));

        expect($errors)->toHaveKey($key)
            ->and(ProductModel::query()->count())->toBe(0);
    })->with([
        'upper case' => [['ERP' => 'x'], 'external_ids'],
        'a space' => [['e rp' => 'x'], 'external_ids'],
        'a dot' => [['e.rp' => 'x'], 'external_ids'],
        '51 characters' => [[str_repeat('s', 51) => 'x'], 'external_ids'],
        'a list, not a map' => [['x'], 'external_ids'],
        'a 192-character id' => [['erp' => str_repeat('i', 192)], 'external_ids.erp'],
        'a number for an id' => [['erp' => 123], 'external_ids.erp'],
        'a nested id' => [['erp' => ['x']], 'external_ids.erp'],
    ]);

    it('refuses the same rules on an owner and on updates', function (): void {
        $product = externalProduct('TEE');

        expect(externalIdErrors(fn () => externalOwner('acme', ['external_ids' => ['ERP' => 'x']])))->toHaveKey('external_ids')
            ->and(externalIdErrors(fn () => UpdateProductData::from(['external_ids' => ['erp' => str_repeat('i', 192)]])))->toHaveKey('external_ids.erp')
            ->and(externalIdErrors(fn () => UpdateOwnerData::from(['external_ids' => null])))->toHaveKey('external_ids')
            ->and(storedExternalIds($product))->toBe([]);
    });

    it('refuses external_ids_synced_at on create and update, even as an empty map', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'V-1']]);
        $sent = ['external_ids_synced_at' => ['erp' => '2026-11-10T09:00:00+00:00']];

        $this->postJson(route('showroom.products.store'), ['identifier' => 'CAP', ...$sent])->assertUnprocessable()->assertJsonValidationErrors('external_ids_synced_at');
        $this->patchJson(route('showroom.products.update', $product), $sent)->assertUnprocessable()->assertJsonValidationErrors('external_ids_synced_at');
        $this->postJson(route('showroom.owners.store'), ['code' => 'globex', 'type' => 'vendor', ...$sent])->assertUnprocessable()->assertJsonValidationErrors('external_ids_synced_at');
        $this->patchJson(route('showroom.owners.update', $owner), $sent)->assertUnprocessable()->assertJsonValidationErrors('external_ids_synced_at');

        expect(externalIdErrors(fn () => CreateProductData::from(['identifier' => 'CAP', 'external_ids_synced_at' => []])))->toHaveKey('external_ids_synced_at')
            ->and(externalIdErrors(fn () => UpdateProductData::from(['external_ids_synced_at' => []])))->toHaveKey('external_ids_synced_at')
            ->and(externalIdErrors(fn () => CreateOwnerData::from(['code' => 'globex', 'type' => 'vendor', 'external_ids_synced_at' => []])))->toHaveKey('external_ids_synced_at')
            ->and(externalIdErrors(fn () => UpdateOwnerData::from(['external_ids_synced_at' => []])))->toHaveKey('external_ids_synced_at')
            ->and(ProductModel::query()->where('identifier', 'CAP')->exists())->toBeFalse()
            ->and(OwnerModel::query()->where('code', 'globex')->exists())->toBeFalse();
    });
});

describe('synced_at', function (): void {
    it('stamps every id a create or an update writes, including one sent again unchanged', function (): void {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $nine = '2026-11-10T09:00:00+00:00';

        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1', 'shop' => 'S-1', 'pim' => 'P-1']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'V-1', 'shop' => 'VS-1']]);

        expect(storedSyncedAt($product))->toBe(['erp' => $nine, 'pim' => $nine, 'shop' => $nine])
            ->and(storedSyncedAt($owner))->toBe(['erp' => $nine, 'shop' => $nine]);

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        $ten = '2026-11-10T10:00:00+00:00';

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['external_ids' => ['shop' => 'S-2', 'pim' => 'P-1', 'd365' => 'D-1']]));
        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['external_ids' => ['erp' => 'V-1']]));

        expect(storedSyncedAt($product))->toBe(['d365' => $ten, 'erp' => $nine, 'pim' => $ten, 'shop' => $ten])
            ->and(storedSyncedAt($owner))->toBe(['erp' => $ten, 'shop' => $nine]);
    });

    it('leaves synced_at alone when an update does not send the map', function (): void {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['enabled' => false]));

        expect(storedSyncedAt($product))->toBe(['erp' => '2026-11-10T09:00:00+00:00']);
    });

    it('touches the ids of many records of one kind in one query, and nothing else', function (): void {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $nine = '2026-11-10T09:00:00+00:00';

        $tee = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1', 'shop' => 'S-1']]);
        $cap = externalProduct('CAP', ['external_ids' => ['erp' => 'E-2']]);
        $mug = externalProduct('MUG', ['external_ids' => ['erp' => 'E-3']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'E-1']]);

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));
        $ten = '2026-11-10T10:00:00+00:00';

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ExternalIds::class)->touch([$tee->id, $cap->id], ProductModel::class);
        app(ExternalIds::class)->touch([], ProductModel::class);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($queries)->toBe(1)
            ->and(storedSyncedAt($tee))->toBe(['erp' => $ten, 'shop' => $ten])
            ->and(storedSyncedAt($cap))->toBe(['erp' => $ten])
            ->and(storedSyncedAt($mug))->toBe(['erp' => $nine])
            ->and(storedSyncedAt($owner))->toBe(['erp' => $nine])
            // Only the sync stamp moves: the ids themselves did not change.
            ->and(ExternalIdModel::query()->where('subject_id', $tee->id)->get()->map(fn (ExternalIdModel $row): ?string => $row->updated_at?->toIso8601String())->unique()->all())->toBe([$nine]);

        // The morph alias works as well as the model class.
        $this->travelTo(Carbon::parse('2026-11-10 11:00:00'));
        app(ExternalIds::class)->touch([$owner->id], $owner->getMorphClass());

        expect(storedSyncedAt($owner))->toBe(['erp' => '2026-11-10T11:00:00+00:00']);
    });
});

describe('the table', function (): void {
    it('has a source, an external id and a nullable synced_at', function (): void {
        $table = Tables::name('external_ids');

        expect(Schema::hasColumns($table, ['subject_type', 'subject_id', 'source', 'external_id', 'synced_at', 'created_at', 'updated_at']))->toBeTrue()
            ->and(Schema::hasColumn($table, 'system'))->toBeFalse()
            ->and(collect(Schema::getColumns($table))->firstWhere('name', 'synced_at')['nullable'] ?? null)->toBeTrue();
    });

    it('refuses a second id from one source for one record at the database, bypassing the service', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);

        expect(fn () => insertExternalIdRow($product, 'erp', 'E-2'))->toThrow(UniqueConstraintViolationException::class);

        // Another source for the same record is fine.
        insertExternalIdRow($product, 'shop', 'E-2');

        expect(storedExternalIds($product))->toBe(['erp' => 'E-1', 'shop' => 'E-2']);
    });

    it('refuses a (source, id) another record of the same kind holds at the database, bypassing the service', function (): void {
        externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);
        $cap = externalProduct('CAP');

        expect(fn () => insertExternalIdRow($cap, 'erp', 'E-1'))->toThrow(UniqueConstraintViolationException::class);
    });
});

describe('uniqueness', function (): void {
    it('refuses a (source, id) another product holds, and writes nothing', function (): void {
        Catalog::apparel();
        externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);

        $errors = externalIdErrors(fn () => externalProduct('CAP', ['external_ids' => ['shop' => 'S-1', 'erp' => 'E-1']]));

        expect($errors)->toHaveKey('external_ids.erp')
            ->and(ProductModel::query()->where('identifier', 'CAP')->exists())->toBeFalse()
            ->and(ExternalIdModel::query()->where('source', 'shop')->exists())->toBeFalse();

        $mug = externalProduct('MUG', ['values' => ['name' => Catalog::value('Mug')], 'external_ids' => ['shop' => 'S-9']]);
        $before = ProductModel::query()->findOrFail($mug->id)->only(['slug', 'values', 'updated_at']);

        $errors = externalIdErrors(fn () => app(UpdateProductAction::class)->execute($mug, UpdateProductData::from([
            'values' => ['name' => Catalog::value('Big mug')],
            'external_ids' => ['shop' => 'S-10', 'erp' => 'E-1'],
        ])));

        expect($errors)->toHaveKey('external_ids.erp')
            ->and(ProductModel::query()->findOrFail($mug->id)->only(['slug', 'values', 'updated_at']))->toEqual($before)
            ->and(storedExternalIds($mug))->toBe(['shop' => 'S-9']);
    });

    it('refuses a (source, id) another owner holds, and writes nothing', function (): void {
        externalOwner('acme', ['external_ids' => ['erp' => 'V-1']]);
        $globex = externalOwner('globex', ['labels' => ['en' => 'Globex']]);

        expect(externalIdErrors(fn () => externalOwner('initech', ['external_ids' => ['erp' => 'V-1']])))->toHaveKey('external_ids.erp')
            ->and(OwnerModel::query()->where('code', 'initech')->exists())->toBeFalse();

        expect(externalIdErrors(fn () => app(UpdateOwnerAction::class)->execute($globex, UpdateOwnerData::from([
            'labels' => ['en' => 'Globex Corp'],
            'external_ids' => ['erp' => 'V-1'],
        ]))))->toHaveKey('external_ids.erp')
            ->and(OwnerModel::query()->findOrFail($globex->id)->labels)->toBe(['en' => 'Globex'])
            ->and(storedExternalIds($globex))->toBe([]);
    });

    it('lets a record keep, or send again, the id it already holds', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['external_ids' => ['erp' => 'E-1']]));

        expect(storedExternalIds($product))->toBe(['erp' => 'E-1']);
    });

    it('lets the same id be held under another source', function (): void {
        externalProduct('TEE', ['external_ids' => ['erp' => 'X-1']]);

        expect(storedExternalIds(externalProduct('CAP', ['external_ids' => ['shop' => 'X-1']])))->toBe(['shop' => 'X-1']);
    });

    it('lets one product and one owner hold the same (source, id)', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => '1001']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => '1001']]);

        expect(storedExternalIds($product))->toBe(['erp' => '1001'])
            ->and(storedExternalIds($owner))->toBe(['erp' => '1001']);
    });

    it('keeps the first record of a batch when a second holds the same (source, id)', function (): void {
        $refused = [];

        DB::transaction(function () use (&$refused): void {
            externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);
            $refused = externalIdErrors(fn () => externalProduct('CAP', ['external_ids' => ['erp' => 'E-1']]));
        });

        expect($refused)->toHaveKey('external_ids.erp')
            ->and(ProductModel::query()->pluck('identifier')->all())->toBe(['TEE'])
            ->and(storedExternalIds(ProductModel::query()->where('identifier', 'TEE')->sole()))->toBe(['erp' => 'E-1']);
    });

    it('refuses an id that differs only by case, where the column compares case-insensitively', function (): void {
        caseInsensitiveColumn('external_ids', 'external_id');

        $tee = externalProduct('TEE', ['external_ids' => ['erp' => 'ABC-1']]);

        expect(externalIdErrors(fn () => externalProduct('CAP', ['external_ids' => ['erp' => 'abc-1']])))->toHaveKey('external_ids.erp')
            ->and(app(ExternalIds::class)->find(ProductModel::class, 'erp', ['Abc-1', 'missing']))->toBe(['Abc-1' => $tee->id]);
    });

    it('refuses an identifier that differs only by case, where the column compares case-insensitively', function (): void {
        caseInsensitiveColumn('products', 'identifier');

        externalProduct('a');

        expect(externalIdErrors(fn () => externalProduct('A')))->toHaveKey('identifier');
    });
});

describe('reading the map', function (): void {
    it('returns the map from the product and owner endpoints and MCP tools', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['shop' => 'S-1', 'erp' => 'E-1']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'V-1']]);

        $this->getJson(route('showroom.products.show', $product))->assertOk()->assertJsonPath('data.external_ids', ['erp' => 'E-1', 'shop' => 'S-1']);
        $this->getJson(route('showroom.products.index'))->assertOk()->assertJsonPath('data.0.external_ids', ['erp' => 'E-1', 'shop' => 'S-1']);
        $this->getJson(route('showroom.owners.show', $owner))->assertOk()->assertJsonPath('data.external_ids', ['erp' => 'V-1']);
        $this->getJson(route('showroom.owners.index'))->assertOk()->assertJsonPath('data.0.external_ids', ['erp' => 'V-1']);

        $this->patchJson(route('showroom.products.update', $product), ['external_ids' => ['shop' => null]])
            ->assertOk()
            ->assertJsonPath('data.external_ids', ['erp' => 'E-1']);
        $this->postJson(route('showroom.owners.store'), ['code' => 'globex', 'type' => 'vendor', 'external_ids' => ['erp' => 'V-2']])
            ->assertCreated()
            ->assertJsonPath('data.external_ids', ['erp' => 'V-2']);

        mcpTool(ShowProductTool::class, ['product' => $product->id])->assertOk()->assertSee('"external_ids":{"erp":"E-1"}');
        mcpTool(ListProductsTool::class)->assertOk()->assertSee('"external_ids":{"erp":"E-1"}');
        mcpTool(ShowOwnerTool::class, ['owner' => $owner->id])->assertOk()->assertSee('"external_ids":{"erp":"V-1"}');
        mcpTool(ListOwnersTool::class)->assertOk()->assertSee('"external_ids":{"erp":"V-1"}');
    });

    it('returns when each source last synced, from the product and owner endpoints and MCP tools', function (): void {
        $this->travelTo(Carbon::parse('2026-11-10 09:00:00'));
        $nine = '2026-11-10T09:00:00+00:00';

        $product = externalProduct('TEE', ['external_ids' => ['shop' => 'S-1', 'erp' => 'E-1']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'V-1']]);
        // A row no sync has stamped yet.
        insertExternalIdRow($owner, 'pim', 'P-1');

        $this->getJson(route('showroom.products.show', $product))->assertOk()->assertJsonPath('data.external_ids_synced_at', ['erp' => $nine, 'shop' => $nine]);
        $this->getJson(route('showroom.products.index'))->assertOk()->assertJsonPath('data.0.external_ids_synced_at', ['erp' => $nine, 'shop' => $nine]);
        $this->getJson(route('showroom.owners.show', $owner))->assertOk()->assertJsonPath('data.external_ids_synced_at', ['erp' => $nine, 'pim' => null]);
        $this->getJson(route('showroom.owners.index'))->assertOk()->assertJsonPath('data.0.external_ids_synced_at', ['erp' => $nine, 'pim' => null]);

        $this->travelTo(Carbon::parse('2026-11-10 10:00:00'));

        $this->patchJson(route('showroom.products.update', $product), ['external_ids' => ['shop' => 'S-2']])
            ->assertOk()
            ->assertJsonPath('data.external_ids_synced_at', ['erp' => $nine, 'shop' => '2026-11-10T10:00:00+00:00']);

        mcpTool(ShowProductTool::class, ['product' => $product->id])->assertOk()->assertSee('"external_ids_synced_at":{"erp":"'.$nine.'","shop":"2026-11-10T10:00:00+00:00"}');
        mcpTool(ListProductsTool::class)->assertOk()->assertSee('"external_ids_synced_at":{"erp":"'.$nine.'","shop":"2026-11-10T10:00:00+00:00"}');
        mcpTool(ShowOwnerTool::class, ['owner' => $owner->id])->assertOk()->assertSee('"external_ids_synced_at":{"erp":"'.$nine.'","pim":null}');
        mcpTool(ListOwnersTool::class)->assertOk()->assertSee('"external_ids_synced_at":{"erp":"'.$nine.'","pim":null}');
    });

    it('serialises an empty map as an object', function (): void {
        $product = externalProduct('TEE');
        $owner = externalOwner('acme');

        foreach ([
            $this->postJson(route('showroom.products.store'), ['identifier' => 'CAP']),
            $this->getJson(route('showroom.products.show', $product)),
            $this->getJson(route('showroom.owners.show', $owner)),
        ] as $response) {
            expect($response->assertSuccessful()->getContent())->toContain('"external_ids":{}')
                ->toContain('"external_ids_synced_at":{}');
        }

        // The JSON the server sends: mcpTool() decodes it to arrays, where {} and [] look alike.
        expect(rawMcpOutput(ShowProductTool::class, ['product' => $product->id]))->toContain('"external_ids":{}')
            ->toContain('"external_ids_synced_at":{}')
            ->and(rawMcpOutput(ShowOwnerTool::class, ['owner' => $owner->id]))->toContain('"external_ids":{}')
            ->toContain('"external_ids_synced_at":{}');
    });

    it('loads the map of many records in one query', function (): void {
        externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);
        externalProduct('CAP', ['external_ids' => ['erp' => 'E-2']]);
        $products = ProductModel::query()->orderBy('identifier')->get();

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ExternalIds::class)->load($products);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($queries)->toBe(1)
            ->and($products->map(fn (ProductModel $product): array => ExternalIds::present($product->externalIds))->all())->toBe([['erp' => 'E-2'], ['erp' => 'E-1']]);
    });

    it('finds the records that hold ids, by subject type and source', function (): void {
        $tee = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);
        $cap = externalProduct('CAP', ['external_ids' => ['erp' => 'E-2', 'shop' => 'E-3']]);
        externalOwner('acme', ['external_ids' => ['erp' => 'E-3']]);

        expect(app(ExternalIds::class)->find(ProductModel::class, 'erp', ['E-1', 'E-2', 'E-3']))->toBe(['E-1' => $tee->id, 'E-2' => $cap->id])
            ->and(app(ExternalIds::class)->find(ProductModel::class, 'erp', []))->toBe([]);
    });
});

describe('the external filter', function (): void {
    it('lists the products and owners holding an id', function (): void {
        externalProduct('TEE', ['external_ids' => ['erp' => 'E-1', 'shop' => 'S-1']]);
        externalProduct('CAP', ['external_ids' => ['erp' => 'E-2', 'shop' => 'S-1x']]);
        externalOwner('acme', ['external_ids' => ['erp' => 'E-1']]);
        externalOwner('globex', ['external_ids' => ['erp' => 'E-2']]);

        $products = fn (array $external): array => collect($this->getJson(route('showroom.products.index', ['external' => $external]))->assertOk()->json('data'))->pluck('identifier')->all();
        $owners = fn (array $external): array => collect($this->getJson(route('showroom.owners.index', ['external' => $external]))->assertOk()->json('data'))->pluck('code')->all();

        expect($products(['erp' => 'E-1']))->toBe(['TEE'])
            ->and($products(['shop' => 'S-1x']))->toBe(['CAP'])
            ->and($products(['erp' => 'E-1', 'shop' => 'S-1']))->toBe(['TEE'])
            ->and($products(['erp' => 'E-1', 'shop' => 'S-1x']))->toBe([])
            ->and($products(['pim' => 'E-1']))->toBe([])
            ->and($owners(['erp' => 'E-2']))->toBe(['globex']);

        $this->getJson(route('showroom.products.index', ['external' => ['ERP' => 'E-1']]))->assertUnprocessable()->assertJsonValidationErrors('external');
        $this->getJson(route('showroom.owners.index', ['external' => ['erp' => str_repeat('i', 192)]]))->assertUnprocessable()->assertJsonValidationErrors('external.erp');
    });

    it('matches values with quotes, backslashes and OR exactly on the database engine', function (string $value): void {
        foreach (['a"b', 'c\\d', 'x OR y', 'x', 'y', 'a', 'b'] as $i => $id) {
            externalProduct('P-'.$i, ['external_ids' => ['erp' => $id]]);
        }

        $matches = collect($this->getJson(route('showroom.products.index', ['external' => ['erp' => $value]]))->assertOk()->json('data'))->pluck('external_ids.erp')->all();

        expect($matches)->toBe([$value]);
    })->with(['a quote' => 'a"b', 'a backslash' => 'c\\d', 'an OR' => 'x OR y']);
});

describe('on the Scout engine', function (): void {
    beforeEach(function (): void {
        $scout = $this->scout = new RecordingScoutEngine;

        app()->singleton(EngineManager::class, fn ($app): EngineManager => new EngineManager($app));
        app(EngineManager::class)->extend('recording', fn (): RecordingScoutEngine => $scout);

        config()->set('scout.driver', 'recording');
        config()->set('showroom.search.engine', 'scout');

        foreach (['a"b', 'c\\d', 'X OR Y', 'x', 'y', 'Café'] as $i => $id) {
            $this->postJson(route('showroom.products.store'), ['identifier' => 'P-'.$i, 'external_ids' => ['erp' => $id]])->assertCreated();
        }
    });

    it('indexes each external id as a normalized "{source}:{id}"', function (): void {
        $documents = collect($this->scout->documents)->pluck('external_ids', 'identifier')->all();

        expect($documents)->toBe([
            'P-0' => ['erp:a"b'],
            'P-1' => ['erp:c\\d'],
            'P-2' => ['erp:x or y'],
            'P-3' => ['erp:x'],
            'P-4' => ['erp:y'],
            'P-5' => ['erp:cafe'],
        ]);
    });

    it('filters on the normalized value, quoted and escaped, never interpolated raw', function (string $value, string $identifier, string $expression): void {
        $this->getJson(route('showroom.products.index', ['external' => ['erp' => $value]]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.identifier', $identifier);

        $search = end($this->scout->searches);

        expect($search->wheres)->toBe([['field' => 'external_ids', 'operator' => '=', 'value' => 'erp:'.mb_strtolower($value)]]);

        // How Scout's Meilisearch engine writes that where into its filter.
        $meilisearch = (new ReflectionClass(MeilisearchEngine::class))->newInstanceWithoutConstructor();

        expect((fn (Builder $builder): string => $this->filters($builder))->call($meilisearch, $search))->toBe($expression);
    })->with([
        'a quote' => ['a"b', 'P-0', 'external_ids="erp:a\"b"'],
        'a backslash' => ['c\\d', 'P-1', 'external_ids="erp:c\\\\d"'],
        'an OR, in another case' => ['x or y', 'P-2', 'external_ids="erp:x or y"'],
    ]);

    it('matches regardless of case and accents', function (): void {
        $this->getJson(route('showroom.products.index', ['external' => ['erp' => 'CAFE']]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.identifier', 'P-5');
    });
});

describe('deleting', function (): void {
    it('deletes the external ids of a deleted product or owner', function (): void {
        $product = externalProduct('TEE', ['external_ids' => ['erp' => 'E-1']]);
        $kept = externalProduct('CAP', ['external_ids' => ['erp' => 'E-2']]);
        $owner = externalOwner('acme', ['external_ids' => ['erp' => 'E-1']]);

        app(DeleteProductAction::class)->execute($product);
        app(DeleteOwnerAction::class)->execute($owner);

        expect(storedExternalIds($product))->toBe([])
            ->and(storedExternalIds($owner))->toBe([])
            ->and(storedExternalIds($kept))->toBe(['erp' => 'E-2']);

        // The id is free again.
        expect(storedExternalIds(externalProduct('MUG', ['external_ids' => ['erp' => 'E-1']])))->toBe(['erp' => 'E-1']);
    });
});

it('puts the external ids in the search document', function (): void {
    $product = externalProduct('TEE', ['external_ids' => ['shop' => 'S-1', 'erp' => 'ABC']]);

    expect(ProductDocument::from($product->fresh() ?? $product)['external_ids'])->toBe(['erp:abc', 'shop:s-1']);
});
