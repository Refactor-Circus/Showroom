<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Owner\Services\OwnerWriter;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

/*
 * OwnerWriter holds the placement, slug, external-id and flag rules that
 * CreateOwnerAction, UpdateOwnerAction and the batch upsert share. It is
 * given the owner's type and parent already loaded, and leaves loading the
 * returned owner's relations to its caller.
 */

beforeEach(function (): void {
    $vendor = OwnerTypeModel::factory()->create(['code' => 'vendor', 'can_be_root' => true]);
    $series = OwnerTypeModel::factory()->create(['code' => 'series', 'can_be_root' => false, 'restricts_parents' => true]);
    $series->parentTypes()->attach($vendor->id);
    OwnerTypeModel::factory()->create(['code' => 'brand', 'can_be_root' => false, 'restricts_parents' => true]);

    FlagModel::factory()->create(['code' => 'pricing_only', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 2]);
});

/**
 * An owner type as a caller preloads it, with its parent types.
 */
function writerType(string $code): OwnerTypeModel
{
    return OwnerTypeModel::query()->with('parentTypes')->where('code', $code)->firstOrFail();
}

/**
 * A stored owner as a caller preloads it, with its type.
 */
function writerOwner(string $code): OwnerModel
{
    return OwnerModel::query()->with('type')->where('code', $code)->firstOrFail();
}

/**
 * Create an owner through the writer, inside a transaction as its callers do.
 *
 * @param  array<string, mixed>  $input
 */
function writerCreate(array $input): OwnerModel
{
    $data = CreateOwnerData::from(['type' => 'vendor', ...$input]);

    return DB::transaction(fn (): OwnerModel => app(OwnerWriter::class)->create(
        $data,
        writerType($data->type),
        $data->parent !== null ? writerOwner($data->parent) : null,
    ));
}

/**
 * Update an owner through the writer with a preloaded type and parent,
 * inside a transaction as its callers do. Returns whether the products
 * beneath need a reindex.
 *
 * @param  array<string, mixed>  $input
 */
function writerUpdate(string $code, array $input): bool
{
    $owner = writerOwner($code);
    $data = UpdateOwnerData::from($input);
    $parent = $data->has('parent') && is_string($data->parent) ? writerOwner($data->parent) : null;

    return DB::transaction(fn (): bool => app(OwnerWriter::class)->update($owner, $data, $parent, writerType($owner->type->code)));
}

/**
 * The validation errors a callback throws, by key.
 *
 * @return array<string, array<int, string>>
 */
function writerErrors(Closure $callback): array
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    throw new RuntimeException('Expected a validation error.');
}

/**
 * The external ids stored for an owner, as {source: id}.
 *
 * @return array<string, string>
 */
function writerExternalIds(OwnerModel $owner): array
{
    return ExternalIdModel::query()
        ->where('subject_type', $owner->getMorphClass())
        ->where('subject_id', $owner->getKey())
        ->orderBy('source')
        ->pluck('external_id', 'source')
        ->all();
}

/**
 * The codes of an owner's own flags as stored.
 *
 * @return list<string>
 */
function writerFlags(OwnerModel $owner): array
{
    /** @var list<string> $codes */
    $codes = DB::table(Tables::name('flag_assignments'))
        ->join(Tables::name('flags'), Tables::name('flags').'.id', '=', Tables::name('flag_assignments').'.flag_id')
        ->where('subject_type', $owner->getMorphClass())
        ->where('subject_id', (string) $owner->getKey())
        ->orderBy('code')
        ->pluck('code')
        ->all();

    return $codes;
}

/**
 * The SQL a callback runs.
 *
 * @return list<string>
 */
function writerQueries(Closure $callback): array
{
    $queries = [];

    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $callback();

    return $queries;
}

/**
 * Turn authorization on with a flag policy that refuses `assign`, and sign in.
 */
function writerSignInWithoutAssign(): void
{
    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, ReadOnlyFlagPolicy::class);

    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));

    test()->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));
}

describe('creating', function (): void {
    it('writes a root owner with its labels, path, slug, external ids and flags', function (): void {
        $owner = writerCreate([
            'code' => 'acme',
            'labels' => ['en' => 'Acme Corp'],
            'external_ids' => ['erp' => 'V-1', 'shop' => 'VS-1'],
            'flags' => ['restricted', 'pricing_only'],
        ]);

        $stored = OwnerModel::query()->findOrFail($owner->id);

        expect($stored->code)->toBe('acme')
            ->and($stored->labels)->toBe(['en' => 'Acme Corp'])
            ->and($stored->parent_id)->toBeNull()
            ->and($stored->depth)->toBe(0)
            ->and($stored->path)->toBe('/'.$owner->id.'/')
            ->and($stored->slug)->toBe('acme-corp')
            ->and(writerExternalIds($stored))->toBe(['erp' => 'V-1', 'shop' => 'VS-1'])
            ->and(writerFlags($stored))->toBe(['pricing_only', 'restricted']);
    });

    it('creates an owner enabled unless the data says otherwise', function (): void {
        expect(writerCreate(['code' => 'acme'])->enabled)->toBeTrue()
            ->and(writerOwner('acme')->enabled)->toBeTrue()
            ->and(writerCreate(['code' => 'globex', 'enabled' => false])->enabled)->toBeFalse()
            ->and(writerOwner('globex')->enabled)->toBeFalse();
    });

    it('places an owner under its parent, below the parent\'s path', function (): void {
        $acme = writerCreate(['code' => 'acme']);
        $series = writerCreate(['code' => 'classic', 'type' => 'series', 'parent' => 'acme']);

        $stored = OwnerModel::query()->findOrFail($series->id);

        expect($stored->parent_id)->toBe($acme->id)
            ->and($stored->depth)->toBe(1)
            ->and($stored->path)->toBe('/'.$acme->id.'/'.$series->id.'/');
    });

    it('uses the type and parent it is given, and leaves the returned owner\'s relations for its caller to load', function (): void {
        writerCreate(['code' => 'acme']);
        $type = writerType('series');
        $parent = writerOwner('acme');

        $owner = null;
        $queries = writerQueries(function () use ($type, $parent, &$owner): void {
            $owner = app(OwnerWriter::class)->create(new CreateOwnerData(code: 'classic', type: 'series', parent: 'acme', externalIds: ['erp' => 'S-1']), $type, $parent);
        });

        expect($owner)->toBeInstanceOf(OwnerModel::class)
            ->and($owner->type)->toBe($type)
            ->and($owner->parent)->toBe($parent)
            ->and($owner->relationLoaded('externalIds'))->toBeFalse()
            ->and(array_filter($queries, fn (string $sql): bool => str_contains($sql, Tables::name('owner_types'))))->toBe([]);
    });

    it('refuses a root of a type that cannot be one, and writes nothing', function (): void {
        $errors = writerErrors(fn () => writerCreate(['code' => 'classic', 'type' => 'series', 'external_ids' => ['erp' => 'S-1'], 'flags' => ['restricted']]));

        expect($errors)->toBe(['parent' => ['A series needs a parent owner.']])
            ->and(OwnerModel::query()->count())->toBe(0)
            ->and(ExternalIdModel::query()->count())->toBe(0)
            ->and(DB::table(Tables::name('flag_assignments'))->count())->toBe(0);
    });

    it('refuses a parent whose type is not among the type\'s parent types', function (): void {
        writerCreate(['code' => 'acme']);
        writerCreate(['code' => 'classic', 'type' => 'series', 'parent' => 'acme']);

        expect(writerErrors(fn () => writerCreate(['code' => 'classic-pro', 'type' => 'series', 'parent' => 'classic'])))
            ->toBe(['parent' => ['A series cannot sit under a series. Allowed parents: vendor.']])
            ->and(writerErrors(fn () => writerCreate(['code' => 'acme-brand', 'type' => 'brand', 'parent' => 'acme'])))
            ->toBe(['parent' => ['A brand cannot sit under a vendor. Allowed parents: none.']])
            ->and(OwnerModel::query()->orderBy('code')->pluck('code')->all())->toBe(['acme', 'classic']);
    });

    it('claims slugs as CreateOwnerAction does, next to the owners already stored', function (): void {
        app(CreateOwnerAction::class)->execute(new CreateOwnerData(code: 'acme', type: 'vendor', labels: ['en' => 'Classic']));

        expect(writerCreate(['code' => 'globex', 'labels' => ['en' => 'Classic']])->slug)->toBe('classic-2')
            ->and(writerCreate(['code' => 'initech', 'labels' => ['en' => 'Classic']])->slug)->toBe('classic-3')
            ->and(writerCreate(['code' => 'hooli', 'labels' => ['fr' => 'Hooli SA']])->slug)->toBe('hooli')
            ->and(app(CreateOwnerAction::class)->execute(new CreateOwnerData(code: 'umbrella', type: 'vendor', labels: ['en' => 'Classic']))->slug)->toBe('classic-4');
    });

    it('refuses a type or parent other than the one the data names', function (): void {
        writerCreate(['code' => 'acme']);
        writerCreate(['code' => 'globex']);

        expect(fn () => app(OwnerWriter::class)->create(new CreateOwnerData(code: 'x', type: 'vendor'), writerType('series'), null))
            ->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(OwnerWriter::class)->create(new CreateOwnerData(code: 'x', type: 'vendor', parent: 'acme'), writerType('vendor'), writerOwner('globex')))
            ->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(OwnerWriter::class)->create(new CreateOwnerData(code: 'x', type: 'vendor', parent: 'acme'), writerType('vendor'), null))
            ->toThrow(InvalidArgumentException::class)
            ->and(OwnerModel::query()->where('code', 'x')->exists())->toBeFalse();
    });
});

describe('updating', function (): void {
    beforeEach(function (): void {
        writerCreate(['code' => 'acme']);
        writerCreate(['code' => 'acme-tools', 'parent' => 'acme']);
        writerCreate(['code' => 'acme-tools-pro', 'parent' => 'acme-tools']);
        writerCreate(['code' => 'globex']);
    });

    it('moves an owner with its descendants, rewriting their paths, and asks for a reindex', function (): void {
        $globex = writerOwner('globex');

        expect(writerUpdate('acme-tools', ['parent' => 'globex']))->toBeTrue();

        $tools = writerOwner('acme-tools');
        $pro = writerOwner('acme-tools-pro');

        expect($tools->parent_id)->toBe($globex->id)
            ->and($tools->depth)->toBe(1)
            ->and($tools->path)->toBe('/'.$globex->id.'/'.$tools->id.'/')
            ->and($pro->parent_id)->toBe($tools->id)
            ->and($pro->depth)->toBe(2)
            ->and($pro->path)->toBe('/'.$globex->id.'/'.$tools->id.'/'.$pro->id.'/');
    });

    it('moves an owner to the root on an explicit null parent', function (): void {
        $tools = writerOwner('acme-tools');

        expect(writerUpdate('acme-tools', ['parent' => null]))->toBeTrue();

        expect(writerOwner('acme-tools')->path)->toBe('/'.$tools->id.'/')
            ->and(writerOwner('acme-tools-pro')->path)->toBe('/'.$tools->id.'/'.writerOwner('acme-tools-pro')->id.'/');
    });

    it('refuses a move under the owner itself or one of its descendants, and changes nothing', function (string $parent): void {
        $before = OwnerModel::query()->orderBy('code')->pluck('path', 'code')->all();

        expect(writerErrors(fn () => writerUpdate('acme', ['parent' => $parent, 'labels' => ['en' => 'Acme']])))
            ->toBe(['parent' => [sprintf('Owner "acme" cannot move under itself or its own descendant "%s".', $parent)]])
            ->and(OwnerModel::query()->orderBy('code')->pluck('path', 'code')->all())->toBe($before)
            ->and(writerOwner('acme')->labels)->toBe([]);
    })->with(['itself' => 'acme', 'a child' => 'acme-tools', 'a grandchild' => 'acme-tools-pro']);

    it('refuses a move that breaks the type\'s placement rules', function (): void {
        writerCreate(['code' => 'classic', 'type' => 'series', 'parent' => 'acme']);
        writerCreate(['code' => 'modern', 'type' => 'series', 'parent' => 'acme']);

        expect(writerErrors(fn () => writerUpdate('classic', ['parent' => null])))->toBe(['parent' => ['A series needs a parent owner.']])
            ->and(writerErrors(fn () => writerUpdate('classic', ['parent' => 'modern'])))->toBe(['parent' => ['A series cannot sit under a series. Allowed parents: vendor.']])
            ->and(writerOwner('classic')->parent_id)->toBe(writerOwner('acme')->id);

        expect(writerUpdate('classic', ['parent' => 'globex']))->toBeTrue()
            ->and(writerOwner('classic')->parent_id)->toBe(writerOwner('globex')->id);
    });

    it('keeps the parent, path and slug when only the labels change, and asks for no reindex', function (): void {
        $before = writerOwner('acme-tools');

        expect(writerUpdate('acme-tools', ['labels' => ['en' => 'Acme Tools']]))->toBeFalse();

        $after = writerOwner('acme-tools');

        expect($after->labels)->toBe(['en' => 'Acme Tools'])
            ->and($after->parent_id)->toBe($before->parent_id)
            ->and($after->path)->toBe($before->path)
            ->and($after->slug)->toBe($before->slug);
    });

    it('asks for no reindex when the same parent is sent back', function (): void {
        expect(writerUpdate('acme-tools', ['parent' => 'acme']))->toBeFalse();
    });

    it('writes the external ids sent, keeping the sources left out', function (): void {
        writerUpdate('acme', ['external_ids' => ['erp' => 'V-1', 'shop' => 'VS-1']]);
        writerUpdate('acme', ['external_ids' => ['erp' => null, 'pim' => 'P-1']]);

        expect(writerExternalIds(writerOwner('acme')))->toBe(['pim' => 'P-1', 'shop' => 'VS-1']);
    });

    it('replaces the flags when they change and asks for a reindex; the same set changes nothing', function (): void {
        expect(writerUpdate('acme', ['flags' => ['restricted']]))->toBeTrue()
            ->and(writerFlags(writerOwner('acme')))->toBe(['restricted'])
            ->and(writerUpdate('acme', ['flags' => ['restricted']]))->toBeFalse()
            ->and(writerUpdate('acme', ['labels' => ['en' => 'Acme']]))->toBeFalse()
            ->and(writerFlags(writerOwner('acme')))->toBe(['restricted'])
            ->and(writerUpdate('acme', ['flags' => []]))->toBeTrue()
            ->and(writerFlags(writerOwner('acme')))->toBe([]);
    });

    it('writes enabled when sent and asks for a reindex when it changes; the same value or none changes nothing', function (): void {
        expect(writerUpdate('acme', ['enabled' => true]))->toBeFalse()
            ->and(writerUpdate('acme', ['enabled' => false]))->toBeTrue()
            ->and(writerOwner('acme')->enabled)->toBeFalse()
            ->and(writerUpdate('acme', ['enabled' => false, 'labels' => ['en' => 'Acme']]))->toBeFalse()
            ->and(writerUpdate('acme', ['labels' => ['en' => 'Acme Inc']]))->toBeFalse()
            ->and(writerOwner('acme')->enabled)->toBeFalse()
            ->and(writerUpdate('acme', ['enabled' => true]))->toBeTrue()
            ->and(writerOwner('acme')->enabled)->toBeTrue();
    });

    it('reads no owner type and no parent when it is given them', function (): void {
        $owner = writerOwner('acme-tools');
        $data = UpdateOwnerData::from(['parent' => 'globex']);
        $parent = writerOwner('globex');
        $type = writerType('vendor');

        $queries = writerQueries(fn () => app(OwnerWriter::class)->update($owner, $data, $parent, $type));

        expect(array_filter($queries, fn (string $sql): bool => str_contains($sql, Tables::name('owner_types'))))->toBe([])
            ->and(array_filter($queries, fn (string $sql): bool => str_starts_with(strtolower($sql), 'select') && preg_match('/\bcode\b/', $sql) === 1))->toBe([])
            ->and($owner->parent)->toBe($parent);
    });

    it('refuses a parent other than the one the data names', function (): void {
        $owner = writerOwner('acme-tools');

        expect(fn () => app(OwnerWriter::class)->update($owner, new UpdateOwnerData(parent: 'globex'), writerOwner('acme')))
            ->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(OwnerWriter::class)->update($owner, new UpdateOwnerData(parent: null), writerOwner('globex')))
            ->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(OwnerWriter::class)->update($owner, new UpdateOwnerData(labels: []), writerOwner('globex')))
            ->toThrow(InvalidArgumentException::class)
            ->and(fn () => app(OwnerWriter::class)->update($owner, new UpdateOwnerData(parent: 'globex'), writerOwner('globex'), writerType('series')))
            ->toThrow(InvalidArgumentException::class)
            ->and(writerOwner('acme-tools')->parent_id)->toBe(writerOwner('acme')->id);
    });
});

describe('the assign ability', function (): void {
    it('is asked when a new owner gets flags, before anything is written', function (): void {
        writerSignInWithoutAssign();

        expect(fn () => writerCreate(['code' => 'acme', 'external_ids' => ['erp' => 'V-1'], 'flags' => ['restricted']]))->toThrow(AuthorizationException::class)
            ->and(OwnerModel::query()->count())->toBe(0)
            ->and(ExternalIdModel::query()->count())->toBe(0);

        // No flags, or an empty set, needs no assign.
        writerCreate(['code' => 'globex']);
        writerCreate(['code' => 'initech', 'flags' => []]);

        expect(OwnerModel::query()->orderBy('code')->pluck('code')->all())->toBe(['globex', 'initech']);
    });

    it('is asked when an owner\'s flags change, before anything is written', function (): void {
        writerCreate(['code' => 'acme', 'flags' => ['pricing_only']]);
        writerCreate(['code' => 'globex']);

        writerSignInWithoutAssign();

        expect(fn () => writerUpdate('acme', ['labels' => ['en' => 'Acme'], 'parent' => 'globex', 'flags' => []]))->toThrow(AuthorizationException::class)
            ->and(writerOwner('acme')->labels)->toBe([])
            ->and(writerOwner('acme')->parent_id)->toBeNull()
            ->and(writerFlags(writerOwner('acme')))->toBe(['pricing_only']);

        // The same set sent back needs no assign.
        expect(writerUpdate('acme', ['labels' => ['en' => 'Acme'], 'flags' => ['pricing_only']]))->toBeFalse()
            ->and(writerOwner('acme')->labels)->toBe(['en' => 'Acme']);
    });
});
