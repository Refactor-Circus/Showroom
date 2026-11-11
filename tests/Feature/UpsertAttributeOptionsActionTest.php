<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Attribute\Actions\UpsertAttributeOptionsAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsData;
use RefactorCircus\Showroom\Domains\Attribute\Data\UpsertAttributeOptionsResult;
use RefactorCircus\Showroom\Domains\Attribute\Enums\AttributeType;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatedEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionCreatingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionSavedEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionSavingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionsUpsertedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionsUpsertingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatedEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeOptionUpdatingEvent;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeOptionModel;
use RefactorCircus\Showroom\Domains\Product\Enums\UpsertMode;
use RefactorCircus\Showroom\Support\Tables;

/*
 * UpsertAttributeOptionsAction loads a batch of option records across
 * attributes: it matches each to a stored option by attribute and code,
 * creates the new ones in bulk, updates the changed ones, and leaves alone
 * what has not changed. A record that fails is reported; its neighbours are
 * still written.
 */

beforeEach(function (): void {
    AttributeModel::factory()->create(['code' => 'color', 'type' => AttributeType::Select]);
    AttributeModel::factory()->create(['code' => 'finish', 'type' => AttributeType::Multiselect]);
    AttributeModel::factory()->create(['code' => 'name', 'type' => AttributeType::Text]);
});

/**
 * @param  list<mixed>  $records
 * @param  array<string, mixed>  $options
 */
function optionsUpsert(array $records, array $options = []): UpsertAttributeOptionsResult
{
    return app(UpsertAttributeOptionsAction::class)->execute(UpsertAttributeOptionsData::from(['records' => $records, ...$options]));
}

/**
 * An option record as a catalog sync sends it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function optionsUpsertRecord(string $attribute, string $code, array $overrides = []): array
{
    return [
        'attribute' => $attribute,
        'code' => $code,
        'labels' => ['en' => ucfirst($code)],
        'sort_order' => 1,
        ...$overrides,
    ];
}

function optionsUpsertOption(string $attribute, string $code): AttributeOptionModel
{
    return AttributeOptionModel::query()
        ->whereBelongsTo(AttributeModel::query()->where('code', $attribute)->sole(), 'attribute')
        ->where('code', $code)
        ->sole();
}

/**
 * Every stored option as `attribute:code`, sorted.
 *
 * @return list<string>
 */
function optionsUpsertKeys(): array
{
    return AttributeOptionModel::query()->with('attribute')->get()
        ->map(fn (AttributeOptionModel $option): string => $option->attribute->code.':'.$option->code)
        ->sort()->values()->all();
}

/**
 * Failures as index => [attribute, code, error keys].
 *
 * @return array<int, array{0: string|null, 1: string|null, 2: list<string>}>
 */
function optionsUpsertFailures(UpsertAttributeOptionsResult $result): array
{
    $failures = [];

    foreach ($result->failures as $failure) {
        $failures[$failure['index']] = [$failure['attribute'], $failure['code'], array_keys($failure['errors'])];
    }

    return $failures;
}

describe('modes and matching', function (): void {
    it('creates new records and updates matched ones by attribute and code, across attributes', function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red')]);

        $result = optionsUpsert([
            optionsUpsertRecord('color', 'red', ['labels' => ['en' => 'Crimson'], 'sort_order' => 5]),
            optionsUpsertRecord('color', 'blue'),
            // The same code on another attribute is another option.
            optionsUpsertRecord('finish', 'red'),
        ]);

        $blue = optionsUpsertOption('color', 'blue');

        expect([$result->created, $result->updated, $result->unchanged, $result->skipped, $result->failed()])->toBe([2, 1, 0, 0, 0])
            ->and(optionsUpsertKeys())->toBe(['color:blue', 'color:red', 'finish:red'])
            ->and(optionsUpsertOption('color', 'red')->labels)->toBe(['en' => 'Crimson'])
            ->and(optionsUpsertOption('color', 'red')->sort_order)->toBe(5)
            ->and($blue->labels)->toBe(['en' => 'Blue'])
            ->and($blue->sort_order)->toBe(1)
            ->and(strlen($blue->id))->toBe(26)
            ->and($blue->created_at)->not->toBeNull()
            ->and($blue->updated_at)->not->toBeNull();
    });

    it('stores a new option as CreateAttributeOptionAction does, with empty labels and order 0 when left out', function (): void {
        $result = optionsUpsert([['attribute' => 'color', 'code' => 'plain']]);

        expect($result->created)->toBe(1)
            ->and(optionsUpsertOption('color', 'plain')->labels)->toBe([])
            ->and(optionsUpsertOption('color', 'plain')->sort_order)->toBe(0);
    });

    it('skips matched records in create mode and new records in update mode', function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red')]);

        $create = optionsUpsert([optionsUpsertRecord('color', 'red', ['labels' => ['en' => 'Created?']]), optionsUpsertRecord('color', 'blue')], ['mode' => 'create']);
        $update = optionsUpsert([optionsUpsertRecord('color', 'red', ['labels' => ['en' => 'Updated']]), optionsUpsertRecord('color', 'green')], ['mode' => UpsertMode::Update->value]);

        expect([$create->created, $create->updated, $create->skipped])->toBe([1, 0, 1])
            ->and([$update->created, $update->updated, $update->skipped])->toBe([0, 1, 1])
            ->and(optionsUpsertKeys())->toBe(['color:blue', 'color:red'])
            ->and(optionsUpsertOption('color', 'red')->labels)->toBe(['en' => 'Updated']);
    });

    it('refuses an option code in another case at code, as the code rule does, rather than matching it', function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red')]);

        $result = optionsUpsert([optionsUpsertRecord('color', 'RED', ['labels' => ['en' => 'Crimson']])]);

        expect(optionsUpsertFailures($result))->toBe([0 => ['color', 'RED', ['code']]])
            ->and(optionsUpsertOption('color', 'red')->labels)->toBe(['en' => 'Red']);
    });

    it('refuses an unknown mode', function (): void {
        expect(fn () => optionsUpsert([optionsUpsertRecord('color', 'red')], ['mode' => 'replace']))
            ->toThrow(fn (ValidationException $exception) => expect($exception->errors())->toHaveKey('mode'));
    });
});

describe('validation', function (): void {
    it('reports each failing record by index, attribute, code and errors, and writes the rest', function (): void {
        $result = optionsUpsert([
            optionsUpsertRecord('color', 'red'),
            optionsUpsertRecord('nope', 'red'),
            optionsUpsertRecord('name', 'red'),
            ['code' => 'orphan'],
            'not an object',
            ['a', 'list'],
            optionsUpsertRecord('color', 'Bad Code'),
            optionsUpsertRecord('color', 'long', ['labels' => ['en' => str_repeat('x', 256)]]),
            optionsUpsertRecord('color', 'negative', ['sort_order' => -1]),
            ['attribute' => 'color'],
            optionsUpsertRecord('finish', 'matte'),
        ]);

        expect([$result->created, $result->failed()])->toBe([2, 9])
            ->and(optionsUpsertFailures($result))->toBe([
                1 => ['nope', 'red', ['attribute']],
                2 => ['name', 'red', ['attribute']],
                3 => [null, 'orphan', ['attribute']],
                4 => [null, null, ['record']],
                5 => [null, null, ['record']],
                6 => ['color', 'Bad Code', ['code']],
                7 => ['color', 'long', ['labels.en']],
                8 => ['color', 'negative', ['sort_order']],
                9 => ['color', null, ['code']],
            ])
            ->and($result->failures[0]['errors']['attribute'][0])->toBe('The selected attribute is invalid.')
            ->and($result->failures[1]['errors']['attribute'][0])->toContain('takes no options')
            ->and($result->failures[2]['errors']['attribute'][0])->toBe('The attribute field is required.')
            ->and(optionsUpsertKeys())->toBe(['color:red', 'finish:matte']);
    });

    it('refuses a matched record that is invalid as UpdateAttributeOptionData, and keeps the option', function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red')]);

        $result = optionsUpsert([optionsUpsertRecord('color', 'red', ['labels' => 'Crimson', 'sort_order' => 'first'])]);

        expect(optionsUpsertFailures($result))->toBe([0 => ['color', 'red', ['labels', 'sort_order']]])
            ->and(optionsUpsertOption('color', 'red')->labels)->toBe(['en' => 'Red']);
    });
});

describe('fields left out', function (): void {
    beforeEach(function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red', ['sort_order' => 3])]);
    });

    it('keeps the labels or sort order a record leaves out', function (): void {
        $labels = optionsUpsert([['attribute' => 'color', 'code' => 'red', 'labels' => ['en' => 'Crimson', 'de' => 'Rot']]]);
        $order = optionsUpsert([['attribute' => 'color', 'code' => 'red', 'sort_order' => 7]]);

        expect([$labels->updated, $order->updated])->toBe([1, 1])
            ->and(optionsUpsertOption('color', 'red')->labels)->toBe(['en' => 'Crimson', 'de' => 'Rot'])
            ->and(optionsUpsertOption('color', 'red')->sort_order)->toBe(7);
    });

    it('clears the labels on an explicit null', function (): void {
        expect(optionsUpsert([['attribute' => 'color', 'code' => 'red', 'labels' => null]])->updated)->toBe(1)
            ->and(optionsUpsertOption('color', 'red')->labels)->toBe([]);
    });
});

describe('unchanged records', function (): void {
    it('writes nothing and announces nothing for a record whose sent fields equal the stored option\'s', function (): void {
        optionsUpsert([
            optionsUpsertRecord('color', 'red', ['labels' => ['en' => 'Red', 'de' => 'Rot']]),
            optionsUpsertRecord('color', 'blue'),
            optionsUpsertRecord('finish', 'matte'),
        ]);
        $before = optionsUpsertOption('color', 'red')->updated_at?->toIso8601String();
        $this->travel(1)->hours();

        Event::fake([AttributeOptionUpdatingActionEvent::class, AttributeOptionUpdatedActionEvent::class, AttributeOptionSavingEvent::class]);

        $writes = 0;
        DB::listen(function (QueryExecuted $query) use (&$writes): void {
            if (! str_starts_with(strtolower($query->sql), 'select')) {
                $writes++;
            }
        });

        $result = optionsUpsert([
            // Labels in another order are the same labels.
            optionsUpsertRecord('color', 'red', ['labels' => ['de' => 'Rot', 'en' => 'Red']]),
            ['attribute' => 'color', 'code' => 'blue', 'sort_order' => 1],
            ['attribute' => 'finish', 'code' => 'matte', 'labels' => ['en' => 'Matte']],
        ]);

        expect([$result->unchanged, $result->updated, $result->failed()])->toBe([3, 0, 0])
            ->and($writes)->toBe(0)
            ->and(optionsUpsertOption('color', 'red')->updated_at?->toIso8601String())->toBe($before);

        Event::assertNotDispatched(AttributeOptionUpdatingActionEvent::class);
        Event::assertNotDispatched(AttributeOptionUpdatedActionEvent::class);
        Event::assertNotDispatched(AttributeOptionSavingEvent::class);
    });

    it('finds a record leaving out every field unchanged', function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red')]);

        expect(optionsUpsert([['attribute' => 'color', 'code' => 'red']])->unchanged)->toBe(1);
    });
});

describe('duplicates', function (): void {
    it('refuses the second record with an attribute and code, whatever became of the first', function (array $first, array $options, array $counts): void {
        optionsUpsert([optionsUpsertRecord('color', 'stored')]);

        $result = optionsUpsert([$first, optionsUpsertRecord('color', $first['code'], ['labels' => ['en' => 'Second']])], $options);

        expect([$result->created, $result->updated, $result->unchanged, $result->skipped])->toBe($counts)
            ->and(optionsUpsertFailures($result)[1])->toBe(['color', $first['code'], ['code']])
            ->and($result->failures[array_key_last($result->failures)]['errors']['code'][0])->toContain('Another record of the batch')
            ->and(AttributeOptionModel::query()->where('labels', json_encode(['en' => 'Second']))->exists())->toBeFalse();
    })->with([
        'created' => [optionsUpsertRecord('color', 'red'), [], [1, 0, 0, 0]],
        'updated' => [optionsUpsertRecord('color', 'stored', ['labels' => ['en' => 'First']]), [], [0, 1, 0, 0]],
        'unchanged' => [optionsUpsertRecord('color', 'stored'), [], [0, 0, 1, 0]],
        'skipped' => [optionsUpsertRecord('color', 'stored'), ['mode' => 'create'], [0, 0, 0, 1]],
        'failed' => [optionsUpsertRecord('color', 'red', ['sort_order' => -1]), [], [0, 0, 0, 0]],
    ]);
});

describe('events', function (): void {
    it('announces the batch with its input and its result', function (): void {
        Event::fake([AttributeOptionsUpsertingActionEvent::class, AttributeOptionsUpsertedActionEvent::class]);

        optionsUpsert([optionsUpsertRecord('color', 'red')], ['mode' => 'create']);

        Event::assertDispatched(AttributeOptionsUpsertingActionEvent::class, fn (AttributeOptionsUpsertingActionEvent $event): bool => count($event->data->records) === 1 && $event->data->mode === UpsertMode::Create);
        Event::assertDispatched(AttributeOptionsUpsertedActionEvent::class, fn (AttributeOptionsUpsertedActionEvent $event): bool => $event->result->created === 1);
    });

    it('fires the single actions\' events and Eloquent\'s for each written option, and none for unchanged or refused ones', function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red'), optionsUpsertRecord('color', 'kept')]);

        /** @var ArrayObject<int, string> $seen */
        $seen = new ArrayObject;
        $name = fn (object $event): string => str_replace(['AttributeOption', 'ActionEvent'], '', class_basename($event));

        Event::listen([
            AttributeOptionsUpsertingActionEvent::class,
            AttributeOptionsUpsertedActionEvent::class,
        ], fn (object $event) => $seen->append($name($event)));
        Event::listen([
            AttributeOptionCreatingActionEvent::class,
            AttributeOptionCreatedActionEvent::class,
            AttributeOptionUpdatingActionEvent::class,
            AttributeOptionUpdatedActionEvent::class,
        ], fn (object $event) => $seen->append($name($event).':'.($event->option->code ?? $event->data->code)));
        Event::listen([
            AttributeOptionSavingEvent::class,
            AttributeOptionCreatingEvent::class,
            AttributeOptionCreatedEvent::class,
            AttributeOptionUpdatingEvent::class,
            AttributeOptionUpdatedEvent::class,
            AttributeOptionSavedEvent::class,
        ], fn (object $event) => $seen->append('model.'.$event->hook().':'.$event->option->code.($event->option->exists ? '' : ' (new)')));

        $eloquent = [];
        AttributeOptionModel::created(function (AttributeOptionModel $option) use (&$eloquent): void {
            $eloquent[] = $option->code;
        });

        optionsUpsert([
            optionsUpsertRecord('color', 'blue'),
            optionsUpsertRecord('color', 'red', ['labels' => ['en' => 'Crimson']]),
            // Unchanged: nothing.
            optionsUpsertRecord('color', 'kept'),
            // Invalid: refused before any event.
            optionsUpsertRecord('nope', 'x'),
            optionsUpsertRecord('finish', 'matte'),
        ]);

        expect($seen->getArrayCopy())->toBe([
            'sUpserting',
            'Creating:blue',
            'model.saving:blue (new)',
            'model.creating:blue (new)',
            'Creating:matte',
            'model.saving:matte (new)',
            'model.creating:matte (new)',
            'model.created:blue',
            'model.saved:blue',
            'Created:blue',
            'model.created:matte',
            'model.saved:matte',
            'Created:matte',
            'Updating:red',
            'model.saving:red',
            'model.updating:red',
            'model.updated:red',
            'model.saved:red',
            'Updated:red',
            'sUpserted',
        ])->and($eloquent)->toBe(['blue', 'matte']);
    });
});

describe('database errors', function (): void {
    it('inserts one by one when the bulk insert fails, and fails only the option that broke it', function (): void {
        // Another writer takes color:clash between the batch's reads and its insert.
        Event::listen(AttributeOptionCreatingActionEvent::class, function (AttributeOptionCreatingActionEvent $event): void {
            if ($event->data->code === 'clash') {
                DB::table(Tables::name('attribute_options'))->insert([
                    'id' => (string) str()->ulid(),
                    'attribute_id' => $event->attribute->id,
                    'code' => 'clash',
                    'labels' => json_encode(['en' => 'Theirs']),
                    'sort_order' => 0,
                ]);
            }
        });

        $result = optionsUpsert([
            optionsUpsertRecord('color', 'red'),
            optionsUpsertRecord('color', 'clash'),
            optionsUpsertRecord('color', 'blue'),
        ]);

        expect(optionsUpsertFailures($result))->toBe([1 => ['color', 'clash', ['code']]])
            ->and($result->failures[0]['errors']['code'][0])->toContain('took the code "clash"')
            ->and($result->failures[0]['errors']['code'][0])->not->toContain('UNIQUE')
            ->and($result->created)->toBe(2)
            ->and(optionsUpsertKeys())->toBe(['color:blue', 'color:clash', 'color:red'])
            ->and(optionsUpsertOption('color', 'clash')->labels)->toBe(['en' => 'Theirs']);
    });

    it('rolls back only the option whose update failed, and reports it without its SQL', function (): void {
        optionsUpsert([optionsUpsertRecord('color', 'red'), optionsUpsertRecord('color', 'blue')]);

        Event::listen(AttributeOptionSavingEvent::class, function (AttributeOptionSavingEvent $event): void {
            if ($event->option->exists && $event->option->code === 'red') {
                throw new UniqueConstraintViolationException('testing', 'update attribute_options', [], new PDOException('UNIQUE constraint failed'));
            }
        });

        $result = optionsUpsert([
            optionsUpsertRecord('color', 'red', ['labels' => ['en' => 'Crimson']]),
            optionsUpsertRecord('color', 'blue', ['labels' => ['en' => 'Navy']]),
        ]);

        expect(optionsUpsertFailures($result))->toBe([0 => ['color', 'red', ['code']]])
            ->and($result->updated)->toBe(1)
            ->and(optionsUpsertOption('color', 'red')->labels)->toBe(['en' => 'Red'])
            ->and(optionsUpsertOption('color', 'blue')->labels)->toBe(['en' => 'Navy']);
    });
});

describe('size', function (): void {
    it('reads attributes and matched options in chunks of showroom.impex.chunk', function (): void {
        foreach (['size', 'shape', 'grade'] as $code) {
            AttributeModel::factory()->create(['code' => $code, 'type' => AttributeType::Select]);
        }

        $records = [];

        foreach (['color', 'finish', 'size', 'shape', 'grade'] as $attribute) {
            $records[] = optionsUpsertRecord($attribute, 'one');
        }

        optionsUpsert($records);

        config()->set('showroom.impex.chunk', 2);

        /** @var list<QueryExecuted> $queries */
        $queries = [];
        $listening = true;
        DB::listen(function (QueryExecuted $query) use (&$queries, &$listening): void {
            if ($listening) {
                $queries[] = $query;
            }
        });

        $result = optionsUpsert($records);
        $listening = false;

        $attributes = array_filter($queries, fn (QueryExecuted $query): bool => str_starts_with($query->sql, 'select * from "'.Tables::name('attributes').'" where "code" in'));
        $options = array_filter($queries, fn (QueryExecuted $query): bool => str_starts_with($query->sql, 'select * from "'.Tables::name('attribute_options').'"'));

        expect($result->unchanged)->toBe(5)
            ->and($attributes)->toHaveCount(3)
            // Three chunks of attribute ids, one of codes.
            ->and($options)->toHaveCount(3)
            ->and(array_map(fn (QueryExecuted $query): int => count($query->bindings), $attributes))->each->toBeLessThanOrEqual(2);
    });
});
