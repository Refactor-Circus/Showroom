<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use RefactorCircus\Impex\Domains\Artifact\Services\PayloadStore;
use RefactorCircus\Impex\Domains\Batch\Data\BatchChunkItem;
use RefactorCircus\Impex\Domains\Flow\Services\FlowRegistry;
use RefactorCircus\Impex\Domains\Run\Enums\RunStatus;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Impex\Facades\Impex;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnersUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;
use RefactorCircus\Showroom\Domains\Transfer\Events\OwnerChunkUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\OwnerChunkUpsertingActionEvent;
use RefactorCircus\Showroom\Impex\Actions\UpsertOwnerChunk;
use RefactorCircus\Showroom\Impex\Flows\UpsertOwnersFlow;
use RefactorCircus\Showroom\Impex\Flows\UpsertProductsFlow;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Impex\Sources\InlineOwnerSource;

/*
 * `showroom:upsert-owners` writes owner records passed in with the run,
 * `showroom.impex.chunk` at a time, each chunk through one call to
 * UpsertOwnersAction. The action's own rules are tested in
 * UpsertOwnersActionTest; this covers the flow's wiring: its payload, its
 * chunks, and how it counts and keeps rows.
 */

beforeEach(function (): void {
    // A vendor may sit at the root or under another vendor; a series only under a vendor.
    $vendor = OwnerTypeModel::factory()->create(['code' => 'vendor', 'can_be_root' => true, 'restricts_parents' => true]);
    $vendor->parentTypes()->attach($vendor->id);
    $series = OwnerTypeModel::factory()->create(['code' => 'series', 'can_be_root' => false, 'restricts_parents' => true]);
    $series->parentTypes()->attach($vendor->id);
});

/**
 * A vendor record as an ERP sends it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ownersFlowRecord(string $code, array $overrides = []): array
{
    return [
        'code' => $code,
        'type' => 'vendor',
        'labels' => ['en' => ucfirst($code)],
        ...$overrides,
    ];
}

function ownersFlowFinishedRun(string $id): RunModel
{
    $run = RunModel::query()->findOrFail($id);

    expect($run->status)->toBe(RunStatus::Completed, (string) json_encode($run->error));

    return $run;
}

/**
 * The run's row counts.
 *
 * @return array<string, mixed>
 */
function ownersFlowResult(string $id): array
{
    return (array) Impex::result(ownersFlowFinishedRun($id));
}

/**
 * Every row failure a finished run kept on its batch items, one item per
 * chunk, in source order.
 *
 * @return list<array{index: int, code: string|null, errors: array<string, list<string>>}>
 */
function ownersFlowFailures(string $runId): array
{
    $payloads = app(PayloadStore::class);
    $failures = [];

    foreach (Impex::batchItems(ownersFlowResult($runId)['batch_id']) as $item) {
        $result = (array) $payloads->get($item->result, $item->result_artifact_id);
        $failures = [...$failures, ...($result['failures'] ?? [])];
    }

    usort($failures, fn (array $a, array $b): int => $a['index'] <=> $b['index']);

    return $failures;
}

/**
 * Write owners through the flow before a test's own run.
 *
 * @param  list<mixed>  $records
 */
function ownersFlowSeed(array $records): void
{
    ownersFlowFinishedRun(Impex::run('showroom:upsert-owners', [$records])->id);
}

/**
 * @return list<string>
 */
function ownersFlowCodes(): array
{
    /** @var list<string> $codes */
    $codes = OwnerModel::query()->orderBy('code')->pluck('code')->all();

    return $codes;
}

/**
 * Record each UpsertOwnersAction call as it starts.
 *
 * @return ArrayObject<int, OwnersUpsertingActionEvent>
 */
function ownersFlowActionCalls(): ArrayObject
{
    /** @var ArrayObject<int, OwnersUpsertingActionEvent> $calls */
    $calls = new ArrayObject;

    Event::listen(OwnersUpsertingActionEvent::class, function (OwnersUpsertingActionEvent $event) use ($calls): void {
        $calls->append($event);
    });

    return $calls;
}

it('is registered with Impex next to showroom:upsert-products', function (): void {
    app()->forgetInstance(FlowRegistry::class);

    expect(ImpexIntegration::UPSERT_OWNERS)->toBe('showroom:upsert-owners')
        ->and(app(ImpexIntegration::class)->flows())->toMatchArray([
            ImpexIntegration::UPSERT => UpsertProductsFlow::class,
            ImpexIntegration::UPSERT_OWNERS => UpsertOwnersFlow::class,
        ])
        ->and(app(FlowRegistry::class)->has('showroom:upsert-owners'))->toBeTrue();
});

it('takes the records as a list or as {"owners": [...]}', function (bool $asWebhookBody): void {
    $records = [ownersFlowRecord('acme'), ['code' => 'acme-tools', 'type' => 'series', 'parent' => 'acme', 'labels' => ['en' => 'Tools']]];

    $run = Impex::run('showroom:upsert-owners', [$asWebhookBody ? ['owners' => $records] : $records]);

    expect(ownersFlowResult($run->id))->toMatchArray(['total' => 2, 'succeeded' => 2, 'failed' => 0, 'unchanged' => 0])
        ->and(ownersFlowCodes())->toBe(['acme', 'acme-tools'])
        ->and(OwnerModel::query()->where('code', 'acme-tools')->sole()->parent?->code)->toBe('acme');
})->with([
    'a list' => [false],
    'a webhook body' => [true],
]);

it('writes each chunk of showroom.impex.chunk records with one call to UpsertOwnersAction', function (): void {
    config()->set('showroom.impex.chunk', 2);
    $calls = ownersFlowActionCalls();

    $run = Impex::run('showroom:upsert-owners', [array_map(fn (int $n): array => ownersFlowRecord('vendor-'.$n), range(1, 5))]);
    $result = ownersFlowResult($run->id);

    expect($result)->toMatchArray(['total' => 5, 'succeeded' => 5, 'failed' => 0])
        ->and(array_map(fn (OwnersUpsertingActionEvent $event): int => count($event->data->records), $calls->getArrayCopy()))->toBe([2, 2, 1])
        ->and(iterator_count(Impex::batchItems($result['batch_id'])))->toBe(3)
        ->and(ownersFlowCodes())->toHaveCount(5);
});

it('passes mode and match through to the action, and counts skipped rows as succeeded (D-006)', function (): void {
    ownersFlowSeed([ownersFlowRecord('acme', ['external_ids' => ['erp' => 'V-1']])]);
    $calls = ownersFlowActionCalls();

    $run = Impex::run('showroom:upsert-owners', [
        'payload' => [
            // Matched by its ERP id, so create mode skips it.
            ['code' => 'acme', 'type' => 'vendor', 'external_ids' => ['erp' => 'V-1'], 'labels' => ['en' => 'Renamed?']],
            ownersFlowRecord('globex', ['external_ids' => ['erp' => 'V-2']]),
            'not an owner',
        ],
        'mode' => 'create',
        'match' => 'erp',
    ]);

    expect(ownersFlowResult($run->id))->toMatchArray(['total' => 3, 'succeeded' => 2, 'failed' => 1, 'unchanged' => 0])
        ->and($calls)->toHaveCount(1)
        ->and($calls[0]->data->mode)->toBe(UpsertMode::Create)
        ->and($calls[0]->data->match)->toBe('erp')
        ->and(OwnerModel::query()->where('code', 'acme')->sole()->labels)->toBe(['en' => 'Acme'])
        ->and(ownersFlowCodes())->toBe(['acme', 'globex']);
});

it('keeps each failed row on its chunk\'s batch item, by its position in the records sent', function (): void {
    config()->set('showroom.impex.chunk', 2);

    $run = Impex::run('showroom:upsert-owners', [[
        ownersFlowRecord('row-0'),
        ownersFlowRecord('row-1'),
        ownersFlowRecord('row-2'),
        ownersFlowRecord('orphan', ['parent' => 'nobody']),
        'not an owner',
    ]]);

    $failures = ownersFlowFailures($run->id);

    expect(ownersFlowResult($run->id))->toMatchArray(['total' => 5, 'succeeded' => 3, 'failed' => 2, 'unchanged' => 0])
        ->and(array_column($failures, 'index'))->toBe([3, 4])
        ->and(array_column($failures, 'code'))->toBe(['orphan', null])
        ->and(array_keys($failures[0]['errors']))->toBe(['parent'])
        ->and(array_keys($failures[1]['errors']))->toBe(['record'])
        ->and(ownersFlowCodes())->toBe(['row-0', 'row-1', 'row-2']);
});

it('fails the run before any chunk when the payload is not a list or {"owners": [...]}', function (array $payload): void {
    Event::fake([OwnerChunkUpsertingActionEvent::class]);

    $run = Impex::run('showroom:upsert-owners', [$payload]);

    expect($run->refresh()->status)->toBe(RunStatus::Failed)
        ->and(ownersFlowCodes())->toBe([]);

    Event::assertNotDispatched(OwnerChunkUpsertingActionEvent::class);
})->with([
    'owners is not an array' => [['owners' => 'acme']],
    'owners is keyed, not a list' => [['owners' => ['first' => ['code' => 'acme', 'type' => 'vendor']]]],
    'no owners key' => [['vendors' => [['code' => 'acme', 'type' => 'vendor']]]],
]);

it('fails a chunk that throws as a whole, counting its rows failed, and goes on with the next', function (): void {
    config()->set('showroom.impex.chunk', 2);

    Event::listen(OwnersUpsertingActionEvent::class, function (OwnersUpsertingActionEvent $event): void {
        if (in_array('boom', array_column($event->data->records, 'code'), true)) {
            throw new RuntimeException('The chunk went down.');
        }
    });

    $run = Impex::run('showroom:upsert-owners', [[
        ownersFlowRecord('boom'),
        ownersFlowRecord('first-mate'),
        ownersFlowRecord('second-chunk'),
        ownersFlowRecord('third'),
    ]]);

    expect(ownersFlowResult($run->id))->toMatchArray(['total' => 4, 'succeeded' => 2, 'failed' => 2, 'unchanged' => 0])
        ->and(ownersFlowCodes())->toBe(['second-chunk', 'third']);
});

it('fails the run when more rows fail than tolerated', function (): void {
    config()->set('showroom.impex.allow_failures', 0.0);

    $run = Impex::run('showroom:upsert-owners', [[ownersFlowRecord('acme'), 'not an owner']]);

    expect($run->refresh()->status)->toBe(RunStatus::Failed);
});

it('reports every row unchanged when the same records are run again', function (): void {
    config()->set('showroom.impex.chunk', 2);

    $records = [
        ownersFlowRecord('acme', ['external_ids' => ['erp' => 'V-1']]),
        ['code' => 'acme-tools', 'type' => 'series', 'parent' => 'acme', 'labels' => ['en' => 'Tools']],
        ownersFlowRecord('globex'),
    ];

    $first = Impex::run('showroom:upsert-owners', [$records]);
    $again = Impex::run('showroom:upsert-owners', [$records]);

    expect(ownersFlowResult($first->id))->toMatchArray(['total' => 3, 'succeeded' => 3, 'failed' => 0, 'unchanged' => 0])
        ->and(ownersFlowResult($again->id))->toMatchArray(['total' => 3, 'succeeded' => 3, 'failed' => 0, 'unchanged' => 3]);
});

it('upserts a chunk through UpsertOwnersAction, reporting failures by their row in the source', function (): void {
    $result = app(UpsertOwnerChunk::class)->execute([
        'mode' => 'upsert',
        'match' => 'code',
        'start' => 10,
        'records' => [ownersFlowRecord('acme'), ownersFlowRecord('orphan', ['parent' => 'nobody']), 'not an owner'],
    ]);

    expect($result)->toMatchArray(['total' => 3, 'created' => 1, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'failed' => 2])
        ->and(array_column($result['failures'], 'index'))->toBe([11, 12])
        ->and($result['failures'][0]['code'])->toBe('orphan')
        ->and($result['failures'][1]['errors'])->toHaveKey('record');
});

it('dispatches its starting and finished events around the chunk, out of the audit log', function (): void {
    Event::fake([OwnerChunkUpsertingActionEvent::class, OwnerChunkUpsertedActionEvent::class]);

    app(UpsertOwnerChunk::class)->execute(['start' => 0, 'records' => [ownersFlowRecord('acme')]]);

    Event::assertDispatched(OwnerChunkUpsertingActionEvent::class, fn (OwnerChunkUpsertingActionEvent $event): bool => ($event->chunk['records'] ?? null) === [ownersFlowRecord('acme')]);
    Event::assertDispatched(OwnerChunkUpsertedActionEvent::class, fn (OwnerChunkUpsertedActionEvent $event): bool => $event->result['created'] === 1);

    expect(class_implements(OwnerChunkUpsertingActionEvent::class))->toHaveKey(UnauditedActionEvent::class)
        ->and(class_implements(OwnerChunkUpsertedActionEvent::class))->toHaveKey(UnauditedActionEvent::class);
});

it('yields inline owner records in chunks, each with its first row in the source', function (): void {
    $records = [['code' => 'a'], ['code' => 'b'], ['code' => 'c'], ['code' => 'd'], ['code' => 'e']];
    $source = new InlineOwnerSource($records, 'update', 'erp', 2);

    $first = $source->chunk(null, 2);
    $last = $source->chunk($first->nextCursor, 2);

    expect($first->complete)->toBeFalse()
        ->and(array_map(fn (BatchChunkItem $item): string => $item->key, $first->items))->toBe(['0', '2'])
        ->and($first->items[1]->payload)->toBe(['mode' => 'update', 'match' => 'erp', 'start' => 2, 'records' => [['code' => 'c'], ['code' => 'd']]])
        ->and($last->complete)->toBeTrue()
        ->and($last->items)->toHaveCount(1)
        ->and($last->items[0]->payload['start'])->toBe(4)
        ->and($last->items[0]->payload['records'])->toBe([['code' => 'e']]);
});
