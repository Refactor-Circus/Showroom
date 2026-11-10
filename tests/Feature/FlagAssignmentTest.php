<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Showroom\Domains\Flag\Actions\DeleteFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Policies\FlagPolicy;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ListOwnersTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\ShowOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\UpdateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Product\Actions\CreateProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\DeleteProductAction;
use RefactorCircus\Showroom\Domains\Product\Actions\UpdateProductAction;
use RefactorCircus\Showroom\Domains\Product\Data\CreateProductData;
use RefactorCircus\Showroom\Domains\Product\Data\UpdateProductData;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\CreateProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ShowProductTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\UpdateProductTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Jobs\QueueProductSyncs;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

/*
 * Products and owners carry flags (restricted, pricing only, ...). A record's
 * own flags are the list it is given; its effective flags add every flag of
 * the owners above it, so a vendor's flag reaches each of its products.
 */

beforeEach(function (): void {
    $this->postJson(route('showroom.owner-types.store'), ['code' => 'vendor', 'parent_types' => ['vendor']])->assertCreated();

    FlagModel::factory()->create(['code' => 'pricing_only', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 2]);
    FlagModel::factory()->create(['code' => 'clearance', 'applies_to' => ['product'], 'sort_order' => 3]);
    FlagModel::factory()->create(['code' => 'preferred', 'applies_to' => ['owner'], 'sort_order' => 4]);
});

/**
 * @param  list<string>  $flags
 */
function flaggedOwner(string $code, ?string $parent = null, array $flags = []): OwnerModel
{
    return app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => $code, 'type' => 'vendor', 'parent' => $parent, 'flags' => $flags]));
}

/**
 * @param  array<string, mixed>  $input
 */
function flaggedProduct(string $identifier, array $input = []): ProductModel
{
    return app(CreateProductAction::class)->execute(CreateProductData::from(['identifier' => $identifier, ...$input]));
}

/**
 * The codes of a record's own flags as stored, by code.
 *
 * @return list<string>
 */
function storedFlags(Model $subject): array
{
    /** @var list<string> $codes */
    $codes = DB::table(Tables::name('flag_assignments'))
        ->join(Tables::name('flags'), Tables::name('flags').'.id', '=', Tables::name('flag_assignments').'.flag_id')
        ->where('subject_type', $subject->getMorphClass())
        ->where('subject_id', (string) $subject->getKey())
        ->orderBy('code')
        ->pluck('code')
        ->all();

    return $codes;
}

/**
 * The validation errors a callback throws, by key.
 *
 * @return array<string, array<int, string>>
 */
function flagValidationErrors(Closure $callback): array
{
    try {
        $callback();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    throw new RuntimeException('Expected a validation error.');
}

/**
 * Turn authorization on, swap the flag policy and sign in.
 *
 * @param  class-string<FlagPolicy>  $policy
 */
function signInWithFlagPolicy(string $policy): void
{
    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, $policy);

    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));

    test()->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));
}

describe('assigning flags', function (): void {
    it('stores the flags a product and an owner are created with', function (): void {
        $owner = flaggedOwner('acme', flags: ['preferred', 'pricing_only']);
        $product = flaggedProduct('TEE', ['owner' => 'acme', 'flags' => ['restricted', 'clearance']]);

        expect(storedFlags($product))->toBe(['clearance', 'restricted'])
            ->and(storedFlags($owner))->toBe(['preferred', 'pricing_only'])
            ->and(DB::table(Tables::name('flag_assignments'))->where('subject_id', $product->id)->value('subject_type'))->toBe($product->getMorphClass());
    });

    it('replaces the own set on update, and keeps it when flags are not sent', function (): void {
        $owner = flaggedOwner('acme', flags: ['pricing_only']);
        $product = flaggedProduct('TEE', ['flags' => ['restricted', 'clearance']]);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['flags' => ['clearance', 'pricing_only']]));
        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['flags' => ['restricted']]));

        expect(storedFlags($product))->toBe(['clearance', 'pricing_only'])
            ->and(storedFlags($owner))->toBe(['restricted']);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['enabled' => false]));
        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['labels' => ['en' => 'Acme']]));

        expect(storedFlags($product))->toBe(['clearance', 'pricing_only'])
            ->and(storedFlags($owner))->toBe(['restricted']);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['flags' => []]));
        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['flags' => []]));

        expect(storedFlags($product))->toBe([])
            ->and(storedFlags($owner))->toBe([]);
    });

    it('refuses an unknown flag, or one that does not apply to the record, at flags.{n}', function (Closure $create, string $key): void {
        $errors = flagValidationErrors($create);

        expect($errors)->toHaveKey($key)
            ->and(ProductModel::query()->count())->toBe(0)
            ->and(OwnerModel::query()->count())->toBe(0)
            ->and(DB::table(Tables::name('flag_assignments'))->count())->toBe(0);
    })->with([
        'an unknown code on a product' => [fn () => flaggedProduct('TEE', ['flags' => ['restricted', 'nope']]), 'flags.1'],
        'an owner-only flag on a product' => [fn () => flaggedProduct('TEE', ['flags' => ['preferred']]), 'flags.0'],
        'an unknown code on an owner' => [fn () => flaggedOwner('acme', flags: ['nope']), 'flags.0'],
        'a product-only flag on an owner' => [fn () => flaggedOwner('acme', flags: ['pricing_only', 'clearance']), 'flags.1'],
        'a code sent twice' => [fn () => flaggedProduct('TEE', ['flags' => ['restricted', 'restricted']]), 'flags.1'],
        'a map, not a list' => [fn () => flaggedProduct('TEE', ['flags' => ['a' => 'restricted']]), 'flags'],
    ]);

    it('refuses the same codes on update, and writes nothing', function (): void {
        $product = flaggedProduct('TEE', ['flags' => ['restricted']]);
        $owner = flaggedOwner('acme', flags: ['pricing_only']);

        expect(flagValidationErrors(fn () => UpdateProductData::from(['flags' => ['restricted', 'preferred']])))->toHaveKey('flags.1')
            ->and(flagValidationErrors(fn () => UpdateOwnerData::from(['flags' => ['clearance']])))->toHaveKey('flags.0')
            ->and(storedFlags($product))->toBe(['restricted'])
            ->and(storedFlags($owner))->toBe(['pricing_only']);
    });

    it('refuses flags over HTTP at flags.{n}', function (): void {
        $this->postJson(route('showroom.products.store'), ['identifier' => 'TEE', 'flags' => ['restricted', 'nope']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('flags.1');

        $this->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor', 'flags' => ['clearance']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('flags.0');
    });

    it('refuses effective_flags sent back on create or update, as it is read-only', function (): void {
        $product = flaggedProduct('TEE');
        $owner = flaggedOwner('acme');

        $this->postJson(route('showroom.products.store'), ['identifier' => 'CAP', 'effective_flags' => []])->assertUnprocessable()->assertJsonValidationErrors('effective_flags');
        $this->postJson(route('showroom.owners.store'), ['code' => 'globex', 'type' => 'vendor', 'effective_flags' => []])->assertUnprocessable()->assertJsonValidationErrors('effective_flags');
        $this->patchJson(route('showroom.products.update', $product), ['effective_flags' => ['restricted']])->assertUnprocessable()->assertJsonValidationErrors('effective_flags');
        $this->patchJson(route('showroom.owners.update', $owner), ['effective_flags' => ['restricted']])->assertUnprocessable()->assertJsonValidationErrors('effective_flags');
    });
});

describe('effective flags', function (): void {
    it('gives an owner its own flags plus every ancestor\'s, down to a grandchild', function (): void {
        $vendor = flaggedOwner('acme', flags: ['pricing_only']);
        $series = flaggedOwner('acme-tools', 'acme');
        $line = flaggedOwner('acme-tools-pro', 'acme-tools', ['restricted']);
        $other = flaggedOwner('globex', flags: ['preferred']);

        $owners = new Collection([$vendor, $series, $line, $other]);

        expect(app(Flags::class)->effective($owners))->toBe([
            $vendor->id => ['pricing_only'],
            $series->id => ['pricing_only'],
            $line->id => ['pricing_only', 'restricted'],
            $other->id => ['preferred'],
        ])->and(app(Flags::class)->own($owners))->toBe([
            $vendor->id => ['pricing_only'],
            $series->id => [],
            $line->id => ['restricted'],
            $other->id => ['preferred'],
        ]);
    });

    it('gives a product its own flags plus its owner\'s effective flags', function (): void {
        flaggedOwner('acme', flags: ['pricing_only']);
        flaggedOwner('acme-tools', 'acme');

        $plain = flaggedProduct('PLAIN', ['owner' => 'acme-tools']);
        $own = flaggedProduct('OWN', ['owner' => 'acme-tools', 'flags' => ['clearance']]);
        $orphan = flaggedProduct('ORPHAN', ['flags' => ['restricted']]);

        expect(app(Flags::class)->effective(new Collection([$plain, $own, $orphan])))->toBe([
            $plain->id => ['pricing_only'],
            $own->id => ['pricing_only', 'clearance'],
            $orphan->id => ['restricted'],
        ]);
    });

    it('gives a variant product the flags of its root model\'s owner', function (): void {
        Catalog::apparel();
        flaggedOwner('acme', flags: ['pricing_only']);
        flaggedOwner('acme-tools', 'acme', ['restricted']);

        $this->postJson(route('showroom.product-models.store'), ['code' => 'polo', 'family_variant' => 'shirts_by_color_size', 'owner' => 'acme-tools'])->assertCreated();
        $this->postJson(route('showroom.product-models.store'), ['code' => 'polo-red', 'parent' => 'polo', 'values' => ['color' => Catalog::value('red')]])->assertCreated();

        $variant = flaggedProduct('POLO-RED-M', ['parent' => 'polo-red', 'values' => ['size' => Catalog::value('m')], 'flags' => ['clearance']]);

        expect(app(Flags::class)->effective(new Collection([$variant->fresh()])))->toBe([
            $variant->id => ['pricing_only', 'restricted', 'clearance'],
        ]);
    });

    it('never lets a product or owner drop a flag it inherits', function (): void {
        flaggedOwner('acme', flags: ['pricing_only']);
        $series = flaggedOwner('acme-tools', 'acme', ['restricted']);
        $product = flaggedProduct('TEE', ['owner' => 'acme-tools', 'flags' => ['pricing_only']]);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['flags' => []]));
        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['flags' => []]));

        expect(app(Flags::class)->effective(new Collection([$product->fresh()]))[$product->id])->toBe(['pricing_only'])
            ->and(app(Flags::class)->effective(new Collection([$series->fresh()]))[$series->id])->toBe(['pricing_only']);
    });

    it('resolves the flags of many records in one query', function (): void {
        flaggedOwner('acme', flags: ['pricing_only']);
        flaggedOwner('acme-tools', 'acme', ['restricted']);
        flaggedProduct('TEE', ['owner' => 'acme-tools', 'flags' => ['clearance']]);
        flaggedProduct('CAP', ['owner' => 'acme']);

        $products = ProductModel::query()->with('owner')->get();
        $owners = OwnerModel::query()->get();

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(Flags::class)->load($products);
        app(Flags::class)->load($owners);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($queries)->toBe(2)
            ->and($products->firstWhere('identifier', 'TEE')?->loadedEffectiveFlags()?->pluck('code')->all())->toBe(['pricing_only', 'restricted', 'clearance'])
            ->and($products->firstWhere('identifier', 'TEE')?->getRelation('flags')->pluck('code')->all())->toBe(['clearance']);
    });
});

describe('reading flags', function (): void {
    it('returns own and effective flags from the endpoints', function (): void {
        $vendor = flaggedOwner('acme', flags: ['pricing_only']);
        $series = flaggedOwner('acme-tools', 'acme', ['restricted']);
        $product = flaggedProduct('TEE', ['owner' => 'acme-tools', 'flags' => ['clearance']]);

        $this->getJson(route('showroom.products.show', $product))->assertOk()
            ->assertJsonPath('data.flags', ['clearance'])
            ->assertJsonPath('data.effective_flags', ['pricing_only', 'restricted', 'clearance']);
        $this->getJson(route('showroom.products.index'))->assertOk()
            ->assertJsonPath('data.0.flags', ['clearance'])
            ->assertJsonPath('data.0.effective_flags', ['pricing_only', 'restricted', 'clearance']);
        $this->getJson(route('showroom.owners.show', $series))->assertOk()
            ->assertJsonPath('data.flags', ['restricted'])
            ->assertJsonPath('data.effective_flags', ['pricing_only', 'restricted']);
        $this->getJson(route('showroom.owners.index', ['flags' => ['restricted']]))->assertOk()
            ->assertJsonPath('data.0.code', 'acme-tools')
            ->assertJsonPath('data.0.effective_flags', ['pricing_only', 'restricted']);

        $this->patchJson(route('showroom.products.update', $product), ['flags' => []])->assertOk()
            ->assertJsonPath('data.flags', [])
            ->assertJsonPath('data.effective_flags', ['pricing_only', 'restricted']);
        $this->patchJson(route('showroom.owners.update', $vendor), ['flags' => ['preferred']])->assertOk()
            ->assertJsonPath('data.flags', ['preferred'])
            ->assertJsonPath('data.effective_flags', ['preferred']);
        $this->postJson(route('showroom.products.store'), ['identifier' => 'CAP', 'owner' => 'acme', 'flags' => ['restricted']])->assertCreated()
            ->assertJsonPath('data.flags', ['restricted'])
            ->assertJsonPath('data.effective_flags', ['restricted', 'preferred']);
        $this->postJson(route('showroom.owners.store'), ['code' => 'globex', 'type' => 'vendor', 'flags' => ['restricted']])->assertCreated()
            ->assertJsonPath('data.flags', ['restricted'])
            ->assertJsonPath('data.effective_flags', ['restricted']);
    });

    it('returns own and effective flags from the MCP tools', function (): void {
        flaggedOwner('acme', flags: ['pricing_only']);
        $owner = flaggedOwner('acme-tools', 'acme');
        $product = flaggedProduct('TEE', ['owner' => 'acme-tools', 'flags' => ['clearance']]);

        mcpTool(ShowProductTool::class, ['product' => $product->id])->assertOk()
            ->assertSee('"flags":["clearance"]')
            ->assertSee('"effective_flags":["pricing_only","clearance"]');
        mcpTool(ListProductsTool::class)->assertOk()->assertSee('"effective_flags":["pricing_only","clearance"]');
        mcpTool(ShowOwnerTool::class, ['owner' => $owner->id])->assertOk()
            ->assertSee('"flags":[]')
            ->assertSee('"effective_flags":["pricing_only"]');
        mcpTool(ListOwnersTool::class)->assertOk()->assertSee('"effective_flags":["pricing_only"]');
        mcpTool(UpdateProductTool::class, ['product' => $product->id, 'flags' => ['restricted']])->assertOk()
            ->assertSee('"flags":["restricted"]')
            ->assertSee('"effective_flags":["pricing_only","restricted"]');
    });
});

describe('the assign ability', function (): void {
    it('is needed by the actions only when a record\'s flags change', function (): void {
        $product = flaggedProduct('TEE', ['flags' => ['restricted', 'clearance']]);
        $owner = flaggedOwner('acme', flags: ['pricing_only']);

        signInWithFlagPolicy(ReadOnlyFlagPolicy::class);

        expect(fn () => flaggedProduct('CAP', ['flags' => ['restricted']]))->toThrow(AuthorizationException::class)
            ->and(fn () => flaggedOwner('globex', flags: ['pricing_only']))->toThrow(AuthorizationException::class)
            ->and(fn () => app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['enabled' => false, 'flags' => ['restricted']])))->toThrow(AuthorizationException::class)
            ->and(fn () => app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['labels' => ['en' => 'Acme'], 'flags' => []])))->toThrow(AuthorizationException::class)
            ->and(ProductModel::query()->pluck('identifier')->all())->toBe(['TEE'])
            ->and(OwnerModel::query()->pluck('code')->all())->toBe(['acme'])
            ->and(ProductModel::query()->findOrFail($product->id)->enabled)->toBeTrue()
            ->and(OwnerModel::query()->findOrFail($owner->id)->labels)->toBe([])
            ->and(storedFlags($product))->toBe(['clearance', 'restricted'])
            ->and(storedFlags($owner))->toBe(['pricing_only']);

        // The same set, in any order, or none at all, needs no assign.
        flaggedProduct('MUG', ['flags' => []]);
        flaggedOwner('initech');
        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['enabled' => false, 'flags' => ['clearance', 'restricted']]));
        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['labels' => ['en' => 'Acme'], 'flags' => ['pricing_only']]));

        expect(ProductModel::query()->findOrFail($product->id)->enabled)->toBeFalse()
            ->and(OwnerModel::query()->findOrFail($owner->id)->labels)->toBe(['en' => 'Acme']);
    });

    it('lets a user the policy allows change flags', function (): void {
        $product = flaggedProduct('TEE');

        signInWithFlagPolicy(FlagPolicy::class);

        app(UpdateProductAction::class)->execute($product, UpdateProductData::from(['flags' => ['restricted']]));

        expect(storedFlags($product))->toBe(['restricted']);
    });

    it('answers 403 over HTTP when flags change without assign, and passes them unchanged', function (): void {
        $product = flaggedProduct('TEE', ['flags' => ['restricted']]);
        $owner = flaggedOwner('acme', flags: ['pricing_only']);

        signInWithFlagPolicy(ReadOnlyFlagPolicy::class);

        $this->postJson(route('showroom.products.store'), ['identifier' => 'CAP', 'flags' => ['restricted']])->assertForbidden();
        $this->postJson(route('showroom.owners.store'), ['code' => 'globex', 'type' => 'vendor', 'flags' => ['restricted']])->assertForbidden();
        $this->patchJson(route('showroom.products.update', $product), ['flags' => ['clearance']])->assertForbidden();
        $this->patchJson(route('showroom.owners.update', $owner), ['flags' => ['restricted']])->assertForbidden();

        $this->postJson(route('showroom.products.store'), ['identifier' => 'CAP'])->assertCreated();
        $this->patchJson(route('showroom.products.update', $product), ['enabled' => false, 'flags' => ['restricted']])->assertOk();
        $this->patchJson(route('showroom.owners.update', $owner), ['labels' => ['en' => 'Acme'], 'flags' => ['pricing_only']])->assertOk();

        expect(storedFlags($product))->toBe(['restricted'])
            ->and(storedFlags($owner))->toBe(['pricing_only'])
            ->and(ProductModel::query()->where('identifier', 'CAP')->sole()->id)->not->toBeEmpty();
    });

    it('answers an MCP error when flags change without assign, and passes them unchanged', function (): void {
        $product = flaggedProduct('TEE', ['flags' => ['restricted']]);
        $owner = flaggedOwner('acme', flags: ['pricing_only']);

        signInWithFlagPolicy(ReadOnlyFlagPolicy::class);

        mcpTool(CreateProductTool::class, ['identifier' => 'CAP', 'flags' => ['restricted']])->assertHasErrors();
        mcpTool(UpdateProductTool::class, ['product' => $product->id, 'flags' => []])->assertHasErrors();
        mcpTool(UpdateOwnerTool::class, ['owner' => $owner->id, 'flags' => ['restricted']])->assertHasErrors();

        mcpTool(UpdateProductTool::class, ['product' => $product->id, 'enabled' => false, 'flags' => ['restricted']])->assertOk();
        mcpTool(UpdateOwnerTool::class, ['owner' => $owner->id, 'flags' => ['pricing_only']])->assertOk();

        expect(ProductModel::query()->where('identifier', 'CAP')->exists())->toBeFalse()
            ->and(storedFlags($product))->toBe(['restricted'])
            ->and(storedFlags($owner))->toBe(['pricing_only']);
    });
});

describe('reindexing', function (): void {
    it('queues a sync of every product beneath an owner whose flags change, or that moves', function (): void {
        flaggedOwner('acme', flags: ['pricing_only']);
        flaggedOwner('globex');
        $series = flaggedOwner('acme-tools', 'acme');

        Queue::fake([QueueProductSyncs::class]);

        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['flags' => ['restricted']]));

        Queue::assertPushed(QueueProductSyncs::class, 1);
        Queue::assertPushed(QueueProductSyncs::class, fn (QueueProductSyncs $job): bool => $job->scope === SyncScope::Owner && $job->id === $series->id);

        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['parent' => 'globex']));

        Queue::assertPushed(QueueProductSyncs::class, 2);

        // A move and a flag change in one update queue one sync.
        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['parent' => 'acme', 'flags' => []]));

        Queue::assertPushed(QueueProductSyncs::class, 3);
    });

    it('queues nothing when the flags are sent unchanged', function (): void {
        $series = flaggedOwner('acme-tools', flags: ['restricted', 'pricing_only']);

        Queue::fake([QueueProductSyncs::class]);

        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['flags' => ['pricing_only', 'restricted'], 'labels' => ['en' => 'Tools']]));

        Queue::assertNotPushed(QueueProductSyncs::class);
    });
});

describe('deleting', function (): void {
    it('deletes the flag assignments of a deleted product or owner', function (): void {
        $owner = flaggedOwner('acme', flags: ['pricing_only', 'restricted']);
        $product = flaggedProduct('TEE', ['flags' => ['restricted', 'clearance']]);
        $kept = flaggedProduct('CAP', ['flags' => ['clearance']]);

        app(DeleteProductAction::class)->execute($product);
        app(DeleteOwnerAction::class)->execute($owner);

        expect(storedFlags($product))->toBe([])
            ->and(storedFlags($owner))->toBe([])
            ->and(storedFlags($kept))->toBe(['clearance']);

        // Nothing holds them now, so the flags can go.
        app(DeleteFlagAction::class)->execute(FlagModel::query()->where('code', 'restricted')->sole());
        app(DeleteFlagAction::class)->execute(FlagModel::query()->where('code', 'pricing_only')->sole());

        expect(FlagModel::query()->orderBy('code')->pluck('code')->all())->toBe(['clearance', 'preferred']);
    });

    it('ignores assignments whose owner no longer exists when deleting a flag', function (): void {
        $gone = flaggedOwner('acme', flags: ['pricing_only', 'restricted']);
        flaggedOwner('globex', flags: ['restricted']);

        // Removed behind the actions' back, leaving its assignments behind.
        DB::table(Tables::name('owners'))->where('id', $gone->id)->delete();

        app(DeleteFlagAction::class)->execute(FlagModel::query()->where('code', 'pricing_only')->sole());

        expect(FlagModel::query()->where('code', 'pricing_only')->exists())->toBeFalse()
            ->and(storedFlags($gone))->toBe(['restricted']);

        // A flag a live owner still holds stays.
        expect(flagValidationErrors(fn () => app(DeleteFlagAction::class)->execute(FlagModel::query()->where('code', 'restricted')->sole())))
            ->toHaveKey('flag')
            ->and(FlagModel::query()->where('code', 'restricted')->exists())->toBeTrue();
    });
});
