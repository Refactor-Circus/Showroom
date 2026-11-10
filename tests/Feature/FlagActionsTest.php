<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Flag\Actions\CreateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\DeleteFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\ListFlagsAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\ShowFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Actions\UpdateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Data\CreateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Data\ListFlagsData;
use RefactorCircus\Showroom\Domains\Flag\Data\UpdateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagCreatedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagCreatingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagDeletedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagDeletingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagUpdatedActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Events\FlagUpdatingActionEvent;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Policies\FlagPolicy;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Support\Tables;
use Workbench\App\Models\User;

/**
 * Assign a flag by writing its row directly: assigning through products and
 * owners is not part of the flag definitions.
 */
function seedFlagAssignment(FlagModel $flag, Model $subject): void
{
    DB::table(Tables::name('flag_assignments'))->insert([
        'flag_id' => $flag->id,
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => (string) $subject->getKey(),
    ]);
}

/**
 * @param  array<string, mixed>  $input
 */
function createFlag(array $input): FlagModel
{
    return app(CreateFlagAction::class)->execute(CreateFlagData::from($input));
}

it('creates a flag with its code, labels, subjects and sort order', function (): void {
    Event::fake([FlagCreatingActionEvent::class, FlagCreatedActionEvent::class]);

    $flag = createFlag([
        'code' => 'pricing_only',
        'labels' => ['en' => 'Pricing only'],
        'applies_to' => ['product', 'owner'],
        'sort_order' => 3,
    ]);

    expect($flag->code)->toBe('pricing_only')
        ->and($flag->labels)->toBe(['en' => 'Pricing only'])
        ->and($flag->applies_to)->toBe(['product', 'owner'])
        ->and($flag->sort_order)->toBe(3)
        ->and($flag->appliesTo(FlagSubject::Product))->toBeTrue()
        ->and(FlagModel::query()->count())->toBe(1);

    Event::assertDispatched(FlagCreatingActionEvent::class, fn (FlagCreatingActionEvent $event): bool => $event->data->code === 'pricing_only');
    Event::assertDispatched(FlagCreatedActionEvent::class, fn (FlagCreatedActionEvent $event): bool => $event->flag->is($flag));
});

it('defaults the labels and sort order', function (): void {
    $flag = createFlag(['code' => 'restricted', 'applies_to' => ['owner']]);

    expect($flag->labels)->toBe([])
        ->and($flag->sort_order)->toBe(0)
        ->and($flag->appliesTo(FlagSubject::Product))->toBeFalse()
        ->and($flag->appliesTo(FlagSubject::Owner))->toBeTrue();
});

it('refuses a flag whose applies_to is not a non-empty subset of product and owner', function (mixed $appliesTo, string $error): void {
    expect(fn () => CreateFlagData::from(['code' => 'restricted', 'applies_to' => $appliesTo]))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey($error));
})->with([
    'missing' => [null, 'applies_to'],
    'empty' => [[], 'applies_to'],
    'not a list' => ['product', 'applies_to'],
    'unknown subject' => [['product', 'variant'], 'applies_to.1'],
    'repeated subject' => [['owner', 'owner'], 'applies_to.0'],
]);

it('refuses a duplicate or malformed code', function (string $code): void {
    createFlag(['code' => 'restricted', 'applies_to' => ['product']]);

    expect(fn () => CreateFlagData::from(['code' => $code, 'applies_to' => ['product']]))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey('code'));
})->with(['restricted', 'Pricing Only', '', str_repeat('a', 101)]);

it('lists flags by sort order, then code, and searches them', function (): void {
    createFlag(['code' => 'restricted', 'applies_to' => ['product'], 'sort_order' => 2]);
    createFlag(['code' => 'pricing_only', 'applies_to' => ['product'], 'sort_order' => 1, 'labels' => ['en' => 'Price list']]);
    createFlag(['code' => 'clearance', 'applies_to' => ['owner'], 'sort_order' => 2]);

    $codes = fn (ListFlagsData $filters): array => collect(app(ListFlagsAction::class)->execute($filters)->items())->pluck('code')->all();

    expect($codes(new ListFlagsData))->toBe(['pricing_only', 'clearance', 'restricted'])
        ->and($codes(new ListFlagsData(search: 'price')))->toBe(['pricing_only'])
        ->and($codes(new ListFlagsData(perPage: 1)))->toBe(['pricing_only']);
});

it('shows a flag', function (): void {
    $flag = createFlag(['code' => 'restricted', 'applies_to' => ['product']]);

    expect(app(ShowFlagAction::class)->execute($flag)->is($flag))->toBeTrue();
});

it('updates only the fields given', function (): void {
    Event::fake([FlagUpdatingActionEvent::class, FlagUpdatedActionEvent::class]);

    $flag = createFlag(['code' => 'restricted', 'labels' => ['en' => 'Restricted'], 'applies_to' => ['product'], 'sort_order' => 4]);

    $flag = app(UpdateFlagAction::class)->execute($flag, UpdateFlagData::from(['applies_to' => ['product', 'owner']]));

    expect($flag->applies_to)->toBe(['product', 'owner'])
        ->and($flag->labels)->toBe(['en' => 'Restricted'])
        ->and($flag->sort_order)->toBe(4);

    $flag = app(UpdateFlagAction::class)->execute($flag, UpdateFlagData::from(['labels' => null, 'sort_order' => 1]));

    expect($flag->fresh()?->labels)->toBe([])
        ->and($flag->sort_order)->toBe(1)
        ->and($flag->applies_to)->toBe(['product', 'owner']);

    Event::assertDispatchedTimes(FlagUpdatingActionEvent::class, 2);
    Event::assertDispatchedTimes(FlagUpdatedActionEvent::class, 2);
});

it('never changes a flag\'s code', function (): void {
    expect(fn () => UpdateFlagData::from(['code' => 'renamed']))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey('code'));
});

it('refuses an empty or unknown applies_to on update', function (mixed $appliesTo, string $error): void {
    expect(fn () => UpdateFlagData::from(['applies_to' => $appliesTo]))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey($error));
})->with([
    'null' => [null, 'applies_to'],
    'empty' => [[], 'applies_to'],
    'unknown subject' => [['variant'], 'applies_to.0'],
]);

it('deletes a flag nothing carries', function (): void {
    Event::fake([FlagDeletingActionEvent::class, FlagDeletedActionEvent::class]);

    $flag = createFlag(['code' => 'restricted', 'applies_to' => ['product']]);

    app(DeleteFlagAction::class)->execute($flag);

    expect(FlagModel::query()->count())->toBe(0);

    Event::assertDispatched(FlagDeletingActionEvent::class);
    Event::assertDispatched(FlagDeletedActionEvent::class);
});

it('refuses to delete a flag that is assigned to anything', function (string $subject): void {
    Event::fake([FlagDeletingActionEvent::class, FlagDeletedActionEvent::class]);

    $flag = createFlag(['code' => 'restricted', 'applies_to' => ['product', 'owner']]);
    seedFlagAssignment($flag, $subject === 'product' ? ProductModel::factory()->create() : OwnerModel::factory()->create());

    expect(fn () => app(DeleteFlagAction::class)->execute($flag))
        ->toThrow(function (ValidationException $e): void {
            expect($e->errors())->toBe(['flag' => ['Flag "restricted" is assigned to 1 record(s). Remove it from them first.']]);
        });

    expect(FlagModel::query()->count())->toBe(1);

    Event::assertDispatched(FlagDeletingActionEvent::class);
    Event::assertNotDispatched(FlagDeletedActionEvent::class);
})->with(['product', 'owner']);

it('refuses to drop a subject type from applies_to while the flag is assigned to it', function (): void {
    $flag = createFlag(['code' => 'restricted', 'applies_to' => ['product', 'owner']]);
    seedFlagAssignment($flag, ProductModel::factory()->create());
    seedFlagAssignment($flag, ProductModel::factory()->create());

    expect(fn () => app(UpdateFlagAction::class)->execute($flag, UpdateFlagData::from(['applies_to' => ['owner'], 'sort_order' => 9])))
        ->toThrow(function (ValidationException $e): void {
            expect($e->errors())->toBe(['applies_to' => ['Flag "restricted" is assigned to 2 product(s). Remove it from them before taking product out of applies_to.']]);
        });

    // Nothing was written.
    $stored = FlagModel::query()->sole();

    expect($stored->applies_to)->toBe(['product', 'owner'])
        ->and($stored->sort_order)->toBe(0);
});

it('lets applies_to drop a subject type the flag is not assigned to', function (): void {
    $flag = createFlag(['code' => 'restricted', 'applies_to' => ['product', 'owner']]);
    seedFlagAssignment($flag, OwnerModel::factory()->create());

    $flag = app(UpdateFlagAction::class)->execute($flag, UpdateFlagData::from(['applies_to' => ['owner']]));

    expect($flag->applies_to)->toBe(['owner']);
});

it('registers FlagPolicy from the config, with an assign ability', function (): void {
    $flag = createFlag(['code' => 'restricted', 'applies_to' => ['product']]);
    $user = User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']);
    $product = ProductModel::factory()->create();

    expect(Gate::getPolicyFor(FlagModel::class))->toBeInstanceOf(FlagPolicy::class);

    $gate = Gate::forUser($user);

    expect($gate->allows('viewAny', FlagModel::class))->toBeTrue()
        ->and($gate->allows('view', $flag))->toBeTrue()
        ->and($gate->allows('create', FlagModel::class))->toBeTrue()
        ->and($gate->allows('update', $flag))->toBeTrue()
        ->and($gate->allows('delete', $flag))->toBeTrue()
        ->and($gate->allows('assign', FlagModel::class))->toBeTrue()
        ->and($gate->allows('assign', [FlagModel::class, $product]))->toBeTrue()
        ->and(Gate::forUser(null)->allows('assign', FlagModel::class))->toBeFalse();
});

it('lets each flag subject name the model it stands for', function (): void {
    expect(FlagSubject::Product->morphClass())->toBe((new ProductModel)->getMorphClass())
        ->and(FlagSubject::Owner->morphClass())->toBe((new OwnerModel)->getMorphClass())
        ->and(FlagSubject::values())->toBe(['product', 'owner']);
});
