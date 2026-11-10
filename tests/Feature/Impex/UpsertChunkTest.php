<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunkItem;
use RefactorCircus\Impex\Domains\Flow\Support\FlowArguments;
use RefactorCircus\Impex\Domains\Run\Enums\RunStatus;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Impex\Facades\Impex;
use RefactorCircus\Impex\Jobs\DetectStream;
use RefactorCircus\Impex\Support\Tables as ImpexTables;
use RefactorCircus\Showroom\Domains\Product\Events\ProductsUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductChunkUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductChunkUpsertingActionEvent;
use RefactorCircus\Showroom\Impex\Actions\UpsertProductChunk;
use RefactorCircus\Showroom\Impex\Flows\UpsertProductsFlow;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Impex\Sources\FileProductSource;
use RefactorCircus\Showroom\Impex\Sources\InlineProductSource;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

beforeEach(function (): void {
    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');

    Catalog::apparel();
});

/**
 * Switch product webhooks on, registering the integration again as it reads
 * the switch at boot, and hold stream detection back so what
 * CaptureProductChanges records stays to be read.
 */
function captureProductWebhooks(): void
{
    config()->set('showroom.impex.webhooks.enabled', true);
    app(ImpexIntegration::class)->register();
    Queue::fake([DetectStream::class]);
}

/**
 * The products recorded for product webhooks, by identifier.
 *
 * @return list<string>
 */
function recordedForWebhooks(): array
{
    return DB::table(ImpexTables::name('stream_touches'))
        ->where('stream', 'showroom.products')
        ->pluck('subject_key')
        ->map(fn (mixed $key): string => (string) $key)
        ->all();
}

/**
 * The arguments `impex:run` reads off its command line for a flow's own
 * options, cast as the command casts them.
 *
 * @param  class-string  $flow
 * @param  array<string, string>  $options
 * @return array<int|string, mixed>
 */
function flowCommandArguments(string $flow, array $options): array
{
    $definition = new InputDefinition([
        new InputOption('argument', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
        ...FlowArguments::options($flow),
    ]);

    return FlowArguments::from($flow, new ArrayInput($options, $definition));
}

it('records an upsert run for product webhooks by default, and nothing with --capture-changes=0', function (): void {
    captureProductWebhooks();

    $default = flowCommandArguments(UpsertProductsFlow::class, []);
    $quiet = flowCommandArguments(UpsertProductsFlow::class, ['--capture-changes' => '0']);

    // A payload of records cannot be typed on a command line, so the records
    // join what the command read from its options.
    $captured = Impex::run('showroom:upsert-products', ['payload' => [['identifier' => 'CLI-ON']], ...$default]);
    $skipped = Impex::run('showroom:upsert-products', ['payload' => [['identifier' => 'CLI-OFF']], ...$quiet]);

    expect($quiet)->toBe(['captureChanges' => false])
        ->and($captured->refresh()->status)->toBe(RunStatus::Completed)
        ->and($skipped->refresh()->status)->toBe(RunStatus::Completed)
        ->and(ProductModel::query()->where('identifier', 'CLI-OFF')->exists())->toBeTrue()
        ->and(recordedForWebhooks())->toContain('CLI-ON')
        ->and(recordedForWebhooks())->not->toContain('CLI-OFF');
});

it('records an import run for product webhooks by default, and nothing with --capture-changes=0', function (): void {
    captureProductWebhooks();

    Storage::disk('assets')->put('incoming/on.jsonl', (string) json_encode(['identifier' => 'FILE-ON']));
    Storage::disk('assets')->put('incoming/off.jsonl', (string) json_encode(['identifier' => 'FILE-OFF']));
    $this->postJson('/showroom/assets', ['code' => 'capture-on', 'path' => 'incoming/on.jsonl'])->assertCreated();
    $this->postJson('/showroom/assets', ['code' => 'capture-off', 'path' => 'incoming/off.jsonl'])->assertCreated();

    $this->artisan('impex:run', ['flow' => 'showroom:import-products', '--asset' => 'capture-on', '--format' => 'jsonl'])->assertSuccessful();
    $this->artisan('impex:run', ['flow' => 'showroom:import-products', '--asset' => 'capture-off', '--format' => 'jsonl', '--capture-changes' => '0'])->assertSuccessful();

    expect(RunModel::query()->where('flow', 'showroom:import-products')->pluck('status')->all())->toBe([RunStatus::Completed, RunStatus::Completed])
        ->and(ProductModel::query()->where('identifier', 'FILE-OFF')->exists())->toBeTrue()
        ->and(recordedForWebhooks())->toContain('FILE-ON')
        ->and(recordedForWebhooks())->not->toContain('FILE-OFF');
});

it('upserts a chunk through UpsertProductsAction, reporting failures by their row in the source', function (): void {
    $result = app(UpsertProductChunk::class)->execute([
        'mode' => 'upsert',
        'capture_changes' => true,
        'start' => 10,
        'records' => [
            ['identifier' => 'CHUNK-1', 'values' => ['name' => Catalog::value('First')]],
            ['identifier' => 'CHUNK-2', 'family' => 'nope'],
            'not json',
        ],
    ]);

    expect($result)->toMatchArray(['total' => 3, 'created' => 1, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'failed' => 2])
        ->and(array_column($result['failures'], 'index'))->toBe([11, 12])
        ->and($result['failures'][0]['identifier'])->toBe('CHUNK-2')
        ->and($result['failures'][0]['errors'])->toHaveKey('family')
        ->and($result['failures'][1]['errors'])->toHaveKey('record')
        ->and(ProductModel::query()->where('identifier', 'CHUNK-1')->exists())->toBeTrue();
});

it('counts a record sent again unchanged as unchanged', function (): void {
    $chunk = ['mode' => 'upsert', 'start' => 0, 'records' => [['identifier' => 'SAME', 'values' => ['name' => Catalog::value('Same')]]]];

    app(UpsertProductChunk::class)->execute($chunk);

    expect(app(UpsertProductChunk::class)->execute($chunk))
        ->toMatchArray(['total' => 1, 'created' => 0, 'updated' => 0, 'unchanged' => 1, 'failed' => 0]);
});

it('passes the mode and capture_changes through to the action', function (bool $capture): void {
    /** @var ArrayObject<int, ProductsUpsertingActionEvent> $seen */
    $seen = new ArrayObject;
    Event::listen(ProductsUpsertingActionEvent::class, function (ProductsUpsertingActionEvent $event) use ($seen): void {
        $seen->append($event);
    });

    $result = app(UpsertProductChunk::class)->execute([
        'mode' => 'update',
        'capture_changes' => $capture,
        'start' => 0,
        'records' => [['identifier' => 'NOT-THERE']],
    ]);

    expect($seen)->toHaveCount(1)
        ->and($seen[0]->data->captureChanges)->toBe($capture)
        ->and($seen[0]->data->mode->value)->toBe('update')
        ->and($result['skipped'])->toBe(1);
})->with([true, false]);

it('dispatches its starting and finished events around the chunk', function (): void {
    Event::fake([ProductChunkUpsertingActionEvent::class, ProductChunkUpsertedActionEvent::class]);

    app(UpsertProductChunk::class)->execute(['start' => 0, 'records' => [['identifier' => 'EVT-1']]]);

    Event::assertDispatched(ProductChunkUpsertingActionEvent::class, fn (ProductChunkUpsertingActionEvent $event): bool => $event->chunk['records'] === [['identifier' => 'EVT-1']]);
    Event::assertDispatched(ProductChunkUpsertedActionEvent::class, fn (ProductChunkUpsertedActionEvent $event): bool => $event->result['created'] === 1);
});

it('yields inline records in chunks, each with its first row in the source', function (): void {
    $records = [['identifier' => 'A'], ['identifier' => 'B'], ['identifier' => 'C'], ['identifier' => 'D'], ['identifier' => 'E']];
    $source = new InlineProductSource($records, 'create', false, 2);

    $first = $source->chunk(null, 2);
    $last = $source->chunk($first->nextCursor, 2);

    expect($first->complete)->toBeFalse()
        ->and(array_map(fn (BatchChunkItem $item): string => $item->key, $first->items))->toBe(['0', '2'])
        ->and($first->items[1]->payload)->toBe(['mode' => 'create', 'capture_changes' => false, 'start' => 2, 'records' => [['identifier' => 'C'], ['identifier' => 'D']]])
        ->and($last->complete)->toBeTrue()
        ->and($last->items)->toHaveCount(1)
        ->and($last->items[0]->payload['start'])->toBe(4)
        ->and($last->items[0]->payload['records'])->toBe([['identifier' => 'E']]);
});

it('yields file records in chunks, counting rows across chunks and skipping blank lines', function (): void {
    Storage::disk('assets')->put('incoming/rows.jsonl', implode("\n", [
        json_encode(['identifier' => 'R-0']),
        '',
        json_encode(['identifier' => 'R-1']),
        'not json',
        json_encode(['identifier' => 'R-3']),
    ]));
    $this->postJson('/showroom/assets', ['code' => 'rows-jsonl', 'path' => 'incoming/rows.jsonl'])->assertCreated();

    $source = new FileProductSource('rows-jsonl', 'jsonl', 'upsert', true, 2);
    $items = [];
    $cursor = null;

    do {
        $chunk = $source->chunk($cursor, 1);
        $items = [...$items, ...$chunk->items];
        $cursor = $chunk->nextCursor;
    } while (! $chunk->complete);

    expect($items)->toHaveCount(2)
        ->and(array_map(fn (BatchChunkItem $item): int => $item->payload['start'], $items))->toBe([0, 2])
        ->and($items[0]->payload['records'])->toBe([['identifier' => 'R-0'], ['identifier' => 'R-1']])
        // A malformed line is still a record, so it fails visibly.
        ->and($items[1]->payload['records'])->toBe(['not json', ['identifier' => 'R-3']])
        ->and($items[1]->payload['capture_changes'])->toBeTrue()
        ->and(array_unique(array_map(fn (BatchChunkItem $item): string => $item->key, $items)))->toHaveCount(2);
});
