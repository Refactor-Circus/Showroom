<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\CreateAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Actions\DeleteAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeData;
use RefactorCircus\Showroom\Domains\Attribute\Data\CreateAttributeGroupData;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupCreatedEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Events\AttributeGroupDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Attribute\Exceptions\AttributeGroupNotEmptyException;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;

/**
 * Record every event of a kind, in order.
 *
 * @param  class-string  $kind
 * @return ArrayObject<int, object>
 */
function recordShowroomEvents(string $kind): ArrayObject
{
    /** @var ArrayObject<int, object> $seen */
    $seen = new ArrayObject;

    Event::listen($kind, function (object $event) use ($seen): void {
        $seen->append($event);
    });

    return $seen;
}

it('maps every Eloquent hook of every model to its own event', function (): void {
    $hooks = ['retrieved', 'creating', 'created', 'updating', 'updated', 'saving', 'saved', 'deleting', 'deleted', 'replicating'];

    $models = array_map(
        fn (string $path): string => 'RefactorCircus\\Showroom\\Domains\\'.basename(dirname($path, 2)).'\\Models\\'.basename($path, '.php'),
        glob(dirname(__DIR__, 2).'/src/Domains/*/Models/*.php') ?: [],
    );

    foreach ($models as $class) {
        $map = (fn (): array => $this->dispatchesEvents)->call(new $class);

        expect(array_keys($map))->toEqualCanonicalizing($hooks)
            ->and(array_map(fn (string $event): bool => is_subclass_of($event, ModelLifecycleEvent::class), array_values($map)))
            ->not->toContain(false);
    }

    expect($models)->toHaveCount(18);
});

it('fires one starting and one finished event for every Action', function (): void {
    $actions = glob(dirname(__DIR__, 2).'/src/Domains/*/Actions/*.php') ?: [];

    foreach ($actions as $path) {
        $source = (string) file_get_contents($path);

        preg_match_all('/(\w+ActionEvent)::dispatch/', $source, $matches);

        expect($matches[1])->toHaveCount(2, basename($path).' should dispatch exactly two action events');

        $namespace = 'RefactorCircus\\Showroom\\Domains\\'.basename(dirname($path, 2)).'\\Events\\';

        [$starting, $finished] = array_map(fn (string $event): string => $namespace.$event, $matches[1]);

        expect(is_subclass_of($starting, ActionStartingEvent::class))->toBeTrue(basename($path).' starts with '.$starting)
            ->and(is_subclass_of($finished, ActionFinishedEvent::class))->toBeTrue(basename($path).' finishes with '.$finished);
    }

    expect($actions)->toHaveCount(77);
});

it('fires the starting and finished events around an action', function (): void {
    $starting = recordShowroomEvents(ActionStartingEvent::class);
    $finished = recordShowroomEvents(ActionFinishedEvent::class);

    $attribute = app(CreateAttributeAction::class)->execute(CreateAttributeData::from(['code' => 'color', 'type' => 'select']));

    expect($starting->getArrayCopy())->toHaveCount(1)
        ->and($starting[0])->toBeInstanceOf(AttributeCreatingActionEvent::class)
        ->and($starting[0]->data->code)->toBe('color')
        ->and($finished[0])->toBeInstanceOf(AttributeCreatedActionEvent::class)
        ->and($finished[0]->attribute->is($attribute))->toBeTrue();
});

it('fires no finished event when an action fails', function (): void {
    Event::fake([AttributeGroupDeletingActionEvent::class, AttributeGroupDeletedActionEvent::class]);

    $group = AttributeGroupModel::factory()->create();
    AttributeModel::factory()->for($group, 'group')->create();

    expect(fn () => app(DeleteAttributeGroupAction::class)->execute($group))->toThrow(AttributeGroupNotEmptyException::class);

    Event::assertDispatched(AttributeGroupDeletingActionEvent::class);
    Event::assertNotDispatched(AttributeGroupDeletedActionEvent::class);
});

it('rolls back a write that fails inside its transaction, and fires no finished event', function (): void {
    $finished = recordShowroomEvents(AttributeGroupCreatedActionEvent::class);

    // The row is written, then the transaction fails.
    Event::listen(AttributeGroupCreatedEvent::class, function (): void {
        throw new RuntimeException('Refused.');
    });

    expect(fn () => app(CreateAttributeGroupAction::class)->execute(new CreateAttributeGroupData(code: 'marketing')))->toThrow(RuntimeException::class, 'Refused.')
        ->and(AttributeGroupModel::query()->count())->toBe(0)
        ->and($finished->getArrayCopy())->toBe([]);
});

it('fires model events as the catalog changes', function (): void {
    $seen = recordShowroomEvents(ModelLifecycleEvent::class);

    $group = AttributeGroupModel::factory()->create();
    $group->update(['sort_order' => 3]);

    $fired = collect($seen)->map(fn (ModelLifecycleEvent $event): string => class_basename($event->model()).'.'.$event->hook());

    expect($fired)->toContain('AttributeGroupModel.creating', 'AttributeGroupModel.created', 'AttributeGroupModel.updated');
});
