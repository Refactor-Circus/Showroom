<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpsertOwnersAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpsertOwnersData;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

/*
 * The batch writes owners through OwnerWriter itself, without the single
 * actions' reads and reloads. The same records, sent one by one through
 * CreateOwnerAction and UpdateOwnerAction, must leave exactly the same rows
 * and fire the same owner events in the same order.
 */

beforeEach(function (): void {
    $vendor = OwnerTypeModel::factory()->create(['code' => 'vendor', 'can_be_root' => true, 'restricts_parents' => true]);
    $vendor->parentTypes()->attach($vendor->id);
    $series = OwnerTypeModel::factory()->create(['code' => 'series', 'can_be_root' => false, 'restricts_parents' => true]);
    $series->parentTypes()->attach($vendor->id);

    FlagModel::factory()->create(['code' => 'pricing_only', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 2]);

    ownersParitySeed();
});

/**
 * The owners every scenario starts from, written by the single action.
 */
function ownersParitySeed(): void
{
    foreach ([
        ['code' => 'acme', 'type' => 'vendor', 'labels' => ['en' => 'Acme'], 'external_ids' => ['erp' => 'V-1'], 'flags' => ['restricted']],
        ['code' => 'globex', 'type' => 'vendor', 'labels' => ['en' => 'Globex']],
        ['code' => 'classic', 'type' => 'series', 'parent' => 'acme', 'labels' => ['en' => 'Classic'], 'external_ids' => ['erp' => 'S-1']],
        ['code' => 'acme-eu', 'type' => 'vendor', 'parent' => 'acme', 'flags' => ['pricing_only']],
    ] as $record) {
        app(CreateOwnerAction::class)->execute(CreateOwnerData::from($record));
    }
}

/**
 * Remove every owner, deepest first, through the delete action.
 */
function ownersParityForget(): void
{
    foreach (OwnerModel::query()->orderByDesc('depth')->orderBy('code')->get() as $owner) {
        app(DeleteOwnerAction::class)->execute($owner);
    }
}

/**
 * Write each record through CreateOwnerAction (a new code) or
 * UpdateOwnerAction (a stored one), as a caller of the single actions
 * would, keeping going past a refused one.
 *
 * @param  list<array<string, mixed>>  $records
 */
function ownersParityOneByOne(array $records): void
{
    foreach ($records as $record) {
        $owner = OwnerModel::query()->where('code', $record['code'])->first();

        try {
            if ($owner === null) {
                app(CreateOwnerAction::class)->execute(CreateOwnerData::from($record));
            } else {
                unset($record['code'], $record['type']);
                app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from($record));
            }
        } catch (ValidationException|AuthorizationException) {
            // Refused, as the batch reports it.
        }
    }
}

/**
 * The stored owners as an explicit column list, without ids or
 * timestamps: parent and path by code, external ids and own flags.
 *
 * @return array<string, array<string, mixed>>
 */
function ownersParityRows(): array
{
    $owners = OwnerModel::query()->with(['type', 'parent'])->orderBy('code')->get();
    $codes = $owners->pluck('code', 'id');
    $rows = [];

    foreach ($owners as $owner) {
        $rows[$owner->code] = [
            'code' => $owner->code,
            'type' => $owner->type->code,
            'labels' => $owner->labels,
            'parent' => $owner->parent?->code,
            'path' => implode('/', array_map(fn (string $id): string => (string) $codes[$id], array_values(array_filter(explode('/', $owner->path))))),
            'depth' => $owner->depth,
            'slug' => $owner->slug,
            'external_ids' => ExternalIdModel::query()
                ->where('subject_type', $owner->getMorphClass())
                ->where('subject_id', $owner->id)
                ->orderBy('source')
                ->pluck('external_id', 'source')
                ->all(),
            'flags' => DB::table(Tables::name('flag_assignments'))
                ->join(Tables::name('flags'), Tables::name('flags').'.id', '=', Tables::name('flag_assignments').'.flag_id')
                ->where('subject_type', $owner->getMorphClass())
                ->where('subject_id', $owner->id)
                ->orderBy('code')
                ->pluck('code')
                ->all(),
        ];
    }

    return $rows;
}

/**
 * Record the owner Action events and the owner write model events, in the
 * order they fire, as `Event:code`. Reads (`retrieved`) are left out: the
 * batch reads less.
 *
 * @return ArrayObject<int, string>
 */
function ownersParityRecordEvents(): ArrayObject
{
    /** @var ArrayObject<int, string> $seen */
    $seen = new ArrayObject;

    Event::listen(OwnerCreatingActionEvent::class, fn (OwnerCreatingActionEvent $event) => $seen->append('OwnerCreating:'.$event->data->code));
    Event::listen(OwnerCreatedActionEvent::class, fn (OwnerCreatedActionEvent $event) => $seen->append('OwnerCreated:'.$event->owner->code));
    Event::listen(OwnerUpdatingActionEvent::class, fn (OwnerUpdatingActionEvent $event) => $seen->append('OwnerUpdating:'.$event->owner->code));
    Event::listen(OwnerUpdatedActionEvent::class, fn (OwnerUpdatedActionEvent $event) => $seen->append('OwnerUpdated:'.$event->owner->code));
    Event::listen(ModelLifecycleEvent::class, function (ModelLifecycleEvent $event) use ($seen): void {
        $model = $event->model();

        if ($model instanceof OwnerModel && $event->hook() !== 'retrieved') {
            $seen->append($event->hook().':'.$model->code);
        }
    });

    return $seen;
}

it('leaves the same rows and fires the same owner events as the single actions', function (array $records): void {
    $events = ownersParityRecordEvents();

    ownersParityOneByOne($records);

    $oneByOne = ownersParityRows();
    $oneByOneEvents = $events->getArrayCopy();

    ownersParityForget();
    ownersParitySeed();
    $events->exchangeArray([]);

    $result = app(UpsertOwnersAction::class)->execute(UpsertOwnersData::from(['records' => $records]));

    expect($result->unchanged)->toBe(0)
        ->and(ownersParityRows())->toEqual($oneByOne)
        ->and($events->getArrayCopy())->toBe($oneByOneEvents);
})->with([
    'create' => [[
        ['code' => 'initech', 'type' => 'vendor', 'labels' => ['en' => 'Initech', 'fr' => 'Initech SA'], 'external_ids' => ['erp' => 'V-2', 'shop' => 'I-1'], 'flags' => ['restricted', 'pricing_only']],
        ['code' => 'modern', 'type' => 'series', 'parent' => 'acme', 'labels' => ['en' => 'Classic']],
        ['code' => 'initech-pro', 'type' => 'series', 'parent' => 'initech'],
        ['code' => 'bare', 'type' => 'vendor'],
    ]],
    'update with fields left out' => [[
        ['code' => 'acme', 'labels' => ['en' => 'Acme Inc']],
        ['code' => 'classic', 'external_ids' => ['shop' => 'C-1', 'erp' => null]],
        ['code' => 'globex', 'type' => 'vendor', 'labels' => null],
    ]],
    'move' => [[
        ['code' => 'classic', 'parent' => 'globex'],
        ['code' => 'globex', 'parent' => 'acme-eu'],
        ['code' => 'acme-eu', 'parent' => null, 'labels' => ['en' => 'Acme EU']],
    ]],
    'flags' => [[
        ['code' => 'acme', 'flags' => ['pricing_only']],
        ['code' => 'classic', 'flags' => ['restricted', 'pricing_only']],
        ['code' => 'acme-eu', 'flags' => []],
    ]],
    'refused' => [[
        ['code' => 'lonely', 'type' => 'series'],
        ['code' => 'acme', 'parent' => 'acme-eu'],
        ['code' => 'nested', 'type' => 'series', 'parent' => 'classic'],
        ['code' => 'classic', 'parent' => null, 'labels' => ['en' => 'Never']],
        ['code' => 'taken', 'type' => 'vendor', 'external_ids' => ['erp' => 'V-1']],
        ['code' => 'kept', 'type' => 'vendor'],
    ]],
]);

it('refuses the same flag changes as the single actions without assign', function (): void {
    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, ReadOnlyFlagPolicy::class);
    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));
    $this->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));

    $records = [
        ['code' => 'acme', 'flags' => []],
        ['code' => 'initech', 'type' => 'vendor', 'flags' => ['restricted']],
        ['code' => 'globex', 'labels' => ['en' => 'Globex Corp'], 'flags' => []],
        ['code' => 'hooli', 'type' => 'vendor', 'flags' => []],
    ];

    ownersParityOneByOne($records);
    $oneByOne = ownersParityRows();

    // The starting owners carry flags: set up without the policy.
    config()->set('showroom.authorization', false);
    ownersParityForget();
    ownersParitySeed();
    config()->set('showroom.authorization', true);

    $result = app(UpsertOwnersAction::class)->execute(UpsertOwnersData::from(['records' => $records]));

    expect(array_column($result->failures, 'code'))->toBe(['acme', 'initech'])
        ->and([$result->created, $result->updated])->toBe([1, 1])
        ->and(ownersParityRows())->toEqual($oneByOne);
});
