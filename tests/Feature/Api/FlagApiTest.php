<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Tools\CreateFlagTool;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Tools\DeleteFlagTool;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Tools\ListFlagsTool;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Tools\ShowFlagTool;
use RefactorCircus\Showroom\Domains\Flag\Mcp\Tools\UpdateFlagTool;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

/**
 * Assign a flag to a product by writing its row directly.
 */
function assignFlagToNewProduct(string $code): void
{
    DB::table(Tables::name('flag_assignments'))->insert([
        'flag_id' => FlagModel::query()->where('code', $code)->value('id'),
        'subject_type' => (new ProductModel)->getMorphClass(),
        'subject_id' => ProductModel::factory()->create()->id,
    ]);
}

/**
 * Point FlagModel at another policy and register the policies again, as the
 * provider does on boot.
 *
 * @param  class-string  $policy
 */
function useFlagPolicy(string $policy): void
{
    config()->set('showroom.policies.'.FlagModel::class, $policy);

    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));
}

it('manages flags over HTTP', function (): void {
    $this->postJson(route('showroom.flags.store'), [
        'code' => 'pricing_only',
        'labels' => ['en' => 'Pricing only'],
        'applies_to' => ['product', 'owner'],
        'sort_order' => 2,
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'pricing_only')
        ->assertJsonPath('data.labels', ['en' => 'Pricing only'])
        ->assertJsonPath('data.applies_to', ['product', 'owner'])
        ->assertJsonPath('data.sort_order', 2);

    $this->postJson(route('showroom.flags.store'), ['code' => 'restricted', 'applies_to' => ['owner']])->assertCreated();

    $this->getJson(route('showroom.flags.index'))
        ->assertOk()
        ->assertJsonPath('data.0.code', 'restricted')
        ->assertJsonPath('data.1.code', 'pricing_only')
        ->assertJsonStructure(['data', 'meta' => ['next_cursor']]);

    $this->getJson(route('showroom.flags.index', ['search' => 'pricing']))->assertOk()->assertJsonCount(1, 'data');

    $this->getJson(route('showroom.flags.show', ['flag' => 'pricing_only']))
        ->assertOk()
        ->assertJsonPath('data.code', 'pricing_only');

    $this->patchJson(route('showroom.flags.update', ['flag' => 'pricing_only']), ['applies_to' => ['product'], 'labels' => ['en' => 'Prices']])
        ->assertOk()
        ->assertJsonPath('data.applies_to', ['product'])
        ->assertJsonPath('data.labels', ['en' => 'Prices'])
        ->assertJsonPath('data.sort_order', 2);

    $this->deleteJson(route('showroom.flags.destroy', ['flag' => 'pricing_only']))->assertNoContent();

    expect(FlagModel::query()->pluck('code')->all())->toBe(['restricted']);
});

it('binds flags by code', function (): void {
    $flag = FlagModel::factory()->create(['code' => 'restricted']);

    $this->getJson(route('showroom.flags.show', ['flag' => 'restricted']))->assertOk();
    $this->getJson(route('showroom.flags.show', ['flag' => $flag->id]))->assertNotFound();
    $this->getJson(route('showroom.flags.show', ['flag' => 'missing']))->assertNotFound();
});

it('maps flag validation to 422', function (): void {
    FlagModel::factory()->create(['code' => 'restricted', 'applies_to' => ['product']]);

    $this->postJson(route('showroom.flags.store'), ['code' => 'restricted', 'applies_to' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'applies_to']);

    $this->postJson(route('showroom.flags.store'), ['code' => 'clearance', 'applies_to' => ['variant']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['applies_to.0']);

    $this->patchJson(route('showroom.flags.update', ['flag' => 'restricted']), ['code' => 'renamed'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);

    expect(FlagModel::query()->pluck('code')->all())->toBe(['restricted']);
});

it('answers a refused delete or narrowing with a validation error', function (): void {
    FlagModel::factory()->create(['code' => 'restricted', 'applies_to' => ['product', 'owner']]);
    assignFlagToNewProduct('restricted');

    $this->deleteJson(route('showroom.flags.destroy', ['flag' => 'restricted']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['flag' => 'Flag "restricted" is assigned to 1 record(s). Remove it from them first.']);

    $this->patchJson(route('showroom.flags.update', ['flag' => 'restricted']), ['applies_to' => ['owner']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['applies_to' => 'Flag "restricted" is assigned to 1 product(s).']);

    expect(FlagModel::query()->sole()->applies_to)->toBe(['product', 'owner']);
});

it('checks every flag request against FlagPolicy', function (): void {
    config()->set('showroom.authorization', true);
    FlagModel::factory()->create(['code' => 'restricted']);

    $this->getJson(route('showroom.flags.index'))->assertUnauthorized();
    $this->postJson(route('showroom.flags.store'), ['code' => 'clearance', 'applies_to' => ['product']])->assertUnauthorized();

    useFlagPolicy(ReadOnlyFlagPolicy::class);
    $this->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));

    $this->getJson(route('showroom.flags.index'))->assertOk();
    $this->getJson(route('showroom.flags.show', ['flag' => 'restricted']))->assertOk();
    $this->postJson(route('showroom.flags.store'), ['code' => 'clearance', 'applies_to' => ['product']])->assertForbidden();
    $this->patchJson(route('showroom.flags.update', ['flag' => 'restricted']), ['sort_order' => 1])->assertForbidden();
    $this->deleteJson(route('showroom.flags.destroy', ['flag' => 'restricted']))->assertForbidden();

    mcpTool(ShowFlagTool::class, ['flag' => 'restricted'])->assertOk();
    mcpTool(CreateFlagTool::class, ['code' => 'clearance', 'applies_to' => ['product']])->assertHasErrors(['Unauthorized.']);
    mcpTool(DeleteFlagTool::class, ['flag' => 'restricted'])->assertHasErrors(['Unauthorized.']);

    expect(FlagModel::query()->pluck('code')->all())->toBe(['restricted']);
});

it('manages flags over MCP', function (): void {
    mcpTool(CreateFlagTool::class, ['code' => 'restricted', 'labels' => ['en' => 'Restricted'], 'applies_to' => ['product', 'owner']])
        ->assertOk()
        ->assertSee('"code":"restricted"')
        ->assertSee('"applies_to":["product","owner"]');

    mcpTool(CreateFlagTool::class, ['code' => 'clearance', 'applies_to' => []])->assertHasErrors();

    mcpTool(ListFlagsTool::class)->assertOk()->assertSee('restricted');
    mcpTool(ShowFlagTool::class, ['flag' => 'restricted'])->assertOk()->assertSee('Restricted');
    mcpTool(ShowFlagTool::class, ['flag' => 'missing'])->assertHasErrors(['Not found.']);

    mcpTool(UpdateFlagTool::class, ['flag' => 'restricted', 'sort_order' => 5])->assertOk()->assertSee('"sort_order":5');
    mcpTool(UpdateFlagTool::class, ['flag' => 'restricted', 'code' => 'renamed'])->assertHasErrors();

    assignFlagToNewProduct('restricted');

    mcpTool(UpdateFlagTool::class, ['flag' => 'restricted', 'applies_to' => ['owner']])
        ->assertHasErrors(['Flag "restricted" is assigned to 1 product(s). Remove it from them before taking product out of applies_to.']);
    mcpTool(DeleteFlagTool::class, ['flag' => 'restricted'])
        ->assertHasErrors(['Flag "restricted" is assigned to 1 record(s). Remove it from them first.']);

    DB::table(Tables::name('flag_assignments'))->delete();

    mcpTool(DeleteFlagTool::class, ['flag' => 'restricted'])->assertOk()->assertSee('"deleted":true')->assertSee('"code":"restricted"');

    expect(FlagModel::query()->count())->toBe(0);
});
