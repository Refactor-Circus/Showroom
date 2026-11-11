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
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionsUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;
use RefactorCircus\Showroom\Domains\Transfer\Events\AttributeOptionChunkUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\AttributeOptionChunkUpsertingActionEvent;
use RefactorCircus\Showroom\Impex\Actions\UpsertAttributeOptionChunk;
use RefactorCircus\Showroom\Impex\Flows\UpsertAttributeOptionsFlow;
use RefactorCircus\Showroom\Impex\Flows\UpsertOwnersFlow;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Impex\Sources\InlineAttributeOptionSource;

/*
 * `showroom:upsert-attribute-options` writes option records passed in with
 * the run, `showroom.impex.chunk` at a time, each chunk through one call to
 * UpsertAttributeOptionsAction. The action's own rules are tested in
 * UpsertAttributeOptionsActionTest; this covers the flow's wiring: its
 * payload, its chunks, and how it counts and keeps rows.
 */

beforeEach(function (): void {
    AttributeModel::factory()->create(['code' => 'color', 'type' => AttributeType::Select]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function optionsFlowRecord(string $code, array $overrides = []): array
{
    return ['attribute' => 'color', 'code' => $code, 'labels' => ['en' => ucfirst($code)], ...$overrides];
}

/**
 * The run's row counts.
 *
 * @return array<string, mixed>
 */
function optionsFlowResult(string $id): array
{
    $run = RunModel::query()->findOrFail($id);

    expect($run->status)->toBe(RunStatus::Completed, (string) json_encode($run->error));

    return (array) Impex::result($run);
}

/**
 * Every row failure a finished run kept on its batch items, in source order.
 *
 * @return list<array{index: int, attribute: string|null, code: string|null, errors: array<string, list<string>>}>
 */
function optionsFlowFailures(string $runId): array
{
    $payloads = app(PayloadStore::class);
    $failures = [];

    foreach (Impex::batchItems(optionsFlowResult($runId)['batch_id']) as $item) {
        $result = (array) $payloads->get($item->result, $item->result_artifact_id);
        $failures = [...$failures, ...($result['failures'] ?? [])];
    }

    usort($failures, fn (array $a, array $b): int => $a['index'] <=> $b['index']);

    return $failures;
}

/**
 * @return list<string>
 */
function optionsFlowCodes(): array
{
    /** @var list<string> $codes */
    $codes = AttributeOptionModel::query()->orderBy('code')->pluck('code')->all();

    return $codes;
}

it('is registered with Impex next to showroom:upsert-owners', function (): void {
    app()->forgetInstance(FlowRegistry::class);

    expect(ImpexIntegration::UPSERT_ATTRIBUTE_OPTIONS)->toBe('showroom:upsert-attribute-options')
        ->and(app(ImpexIntegration::class)->flows())->toMatchArray([
            ImpexIntegration::UPSERT_OWNERS => UpsertOwnersFlow::class,
            ImpexIntegration::UPSERT_ATTRIBUTE_OPTIONS => UpsertAttributeOptionsFlow::class,
        ])
        ->and(app(FlowRegistry::class)->has('showroom:upsert-attribute-options'))->toBeTrue();
});

it('takes the records as a list or as {"options": [...]}', function (bool $asWebhookBody): void {
    $records = [optionsFlowRecord('red'), optionsFlowRecord('blue')];

    $run = Impex::run('showroom:upsert-attribute-options', [$asWebhookBody ? ['options' => $records] : $records]);

    expect(optionsFlowResult($run->id))->toMatchArray(['total' => 2, 'succeeded' => 2, 'failed' => 0, 'unchanged' => 0])
        ->and(optionsFlowCodes())->toBe(['blue', 'red']);
})->with([
    'a list' => [false],
    'a webhook body' => [true],
]);

it('writes each chunk of showroom.impex.chunk records with one call to UpsertAttributeOptionsAction, in its mode', function (): void {
    config()->set('showroom.impex.chunk', 2);

    /** @var ArrayObject<int, AttributeOptionsUpsertingActionEvent> $calls */
    $calls = new ArrayObject;
    Event::listen(AttributeOptionsUpsertingActionEvent::class, fn (AttributeOptionsUpsertingActionEvent $event) => $calls->append($event));

    $run = Impex::run('showroom:upsert-attribute-options', [
        'payload' => array_map(fn (int $n): array => optionsFlowRecord('option_'.$n), range(1, 5)),
        'mode' => 'create',
    ]);
    $result = optionsFlowResult($run->id);

    expect($result)->toMatchArray(['total' => 5, 'succeeded' => 5, 'failed' => 0])
        ->and(array_map(fn (AttributeOptionsUpsertingActionEvent $event): int => count($event->data->records), $calls->getArrayCopy()))->toBe([2, 2, 1])
        ->and($calls[0]->data->mode)->toBe(UpsertMode::Create)
        ->and(iterator_count(Impex::batchItems($result['batch_id'])))->toBe(3)
        ->and(optionsFlowCodes())->toHaveCount(5);
});

it('keeps each failed row on its chunk\'s batch item, by its position in the records sent', function (): void {
    config()->set('showroom.impex.chunk', 2);

    $run = Impex::run('showroom:upsert-attribute-options', [[
        optionsFlowRecord('row_0'),
        optionsFlowRecord('row_1'),
        optionsFlowRecord('row_2'),
        optionsFlowRecord('lost', ['attribute' => 'nope']),
        'not an option',
    ]]);

    $failures = optionsFlowFailures($run->id);

    expect(optionsFlowResult($run->id))->toMatchArray(['total' => 5, 'succeeded' => 3, 'failed' => 2, 'unchanged' => 0])
        ->and(array_column($failures, 'index'))->toBe([3, 4])
        ->and(array_column($failures, 'attribute'))->toBe(['nope', null])
        ->and(array_keys($failures[0]['errors']))->toBe(['attribute'])
        ->and(array_keys($failures[1]['errors']))->toBe(['record']);
});

it('fails the run before any chunk when the payload is not a list or {"options": [...]}', function (array $payload): void {
    Event::fake([AttributeOptionChunkUpsertingActionEvent::class]);

    $run = Impex::run('showroom:upsert-attribute-options', [$payload]);

    expect($run->refresh()->status)->toBe(RunStatus::Failed)
        ->and(optionsFlowCodes())->toBe([]);

    Event::assertNotDispatched(AttributeOptionChunkUpsertingActionEvent::class);
})->with([
    'options is not an array' => [['options' => 'red']],
    'options is keyed, not a list' => [['options' => ['first' => ['attribute' => 'color', 'code' => 'red']]]],
    'no options key' => [['values' => [['attribute' => 'color', 'code' => 'red']]]],
]);

it('fails the run when more rows fail than tolerated', function (): void {
    config()->set('showroom.impex.allow_failures', 0.0);

    $run = Impex::run('showroom:upsert-attribute-options', [[optionsFlowRecord('red'), 'not an option']]);

    expect($run->refresh()->status)->toBe(RunStatus::Failed);
});

it('reports every row unchanged when the same records are run again', function (): void {
    $records = [optionsFlowRecord('red'), optionsFlowRecord('blue')];

    $first = Impex::run('showroom:upsert-attribute-options', [$records]);
    $again = Impex::run('showroom:upsert-attribute-options', [$records]);

    expect(optionsFlowResult($first->id))->toMatchArray(['total' => 2, 'succeeded' => 2, 'unchanged' => 0])
        ->and(optionsFlowResult($again->id))->toMatchArray(['total' => 2, 'succeeded' => 2, 'unchanged' => 2]);
});

it('upserts a chunk through UpsertAttributeOptionsAction, reporting failures by their row in the source', function (): void {
    $result = app(UpsertAttributeOptionChunk::class)->execute([
        'mode' => 'upsert',
        'start' => 10,
        'records' => [optionsFlowRecord('red'), optionsFlowRecord('Bad Code'), 'not an option'],
    ]);

    expect($result)->toMatchArray(['total' => 3, 'created' => 1, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'failed' => 2])
        ->and(array_column($result['failures'], 'index'))->toBe([11, 12])
        ->and($result['failures'][0]['code'])->toBe('Bad Code')
        ->and($result['failures'][1]['errors'])->toHaveKey('record');
});

it('dispatches its starting and finished events around the chunk, out of the audit log', function (): void {
    Event::fake([AttributeOptionChunkUpsertingActionEvent::class, AttributeOptionChunkUpsertedActionEvent::class]);

    app(UpsertAttributeOptionChunk::class)->execute(['start' => 0, 'records' => [optionsFlowRecord('red')]]);

    Event::assertDispatched(AttributeOptionChunkUpsertingActionEvent::class, fn (AttributeOptionChunkUpsertingActionEvent $event): bool => ($event->chunk['records'] ?? null) === [optionsFlowRecord('red')]);
    Event::assertDispatched(AttributeOptionChunkUpsertedActionEvent::class, fn (AttributeOptionChunkUpsertedActionEvent $event): bool => $event->result['created'] === 1);

    expect(class_implements(AttributeOptionChunkUpsertingActionEvent::class))->toHaveKey(UnauditedActionEvent::class)
        ->and(class_implements(AttributeOptionChunkUpsertedActionEvent::class))->toHaveKey(UnauditedActionEvent::class);
});

it('yields inline option records in chunks, each with its first row in the source', function (): void {
    $records = [['code' => 'a'], ['code' => 'b'], ['code' => 'c'], ['code' => 'd'], ['code' => 'e']];
    $source = new InlineAttributeOptionSource($records, 'update', 2);

    $first = $source->chunk(null, 2);
    $last = $source->chunk($first->nextCursor, 2);

    expect($first->complete)->toBeFalse()
        ->and(array_map(fn (BatchChunkItem $item): string => $item->key, $first->items))->toBe(['0', '2'])
        ->and($first->items[1]->payload)->toBe(['mode' => 'update', 'start' => 2, 'records' => [['code' => 'c'], ['code' => 'd']]])
        ->and($last->complete)->toBeTrue()
        ->and($last->items)->toHaveCount(1)
        ->and($last->items[0]->payload['start'])->toBe(4);
});
