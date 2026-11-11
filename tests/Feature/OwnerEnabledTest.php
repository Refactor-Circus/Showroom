<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\CreateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Mcp\Tools\UpdateOwnerTool;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ListProductsTool;
use RefactorCircus\Showroom\Domains\Product\Mcp\Tools\ShowProductTool;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Search\Enums\SyncScope;
use RefactorCircus\Showroom\Jobs\QueueProductSyncs;
use RefactorCircus\Showroom\Support\Tables;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

/*
 * Owners are enabled or not. A product is effectively enabled only while it
 * is, and so is every owner of its chain: its effective owner (a variant's
 * root model's owner) and that owner's ancestors. Disabling a vendor
 * switches off every product beneath it without touching their own flag.
 */

beforeEach(function (): void {
    OwnerTypeModel::factory()->create(['code' => 'vendor']);
});

/**
 * @param  array<string, mixed>  $input
 */
function enabledOwner(string $code, array $input = []): OwnerModel
{
    return app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => $code, 'type' => 'vendor', ...$input]));
}

function enabledOwnerByCode(string $code): OwnerModel
{
    return OwnerModel::query()->where('code', $code)->sole();
}

/**
 * Each product's effective_enabled as the listing returns it, by identifier.
 *
 * @return array<string, bool>
 */
function listedEffectiveEnabled(): array
{
    /** @var array<string, bool> $listed */
    $listed = collect(test()->getJson(route('showroom.products.index', ['per_page' => 100]))->assertOk()->json('data'))
        ->pluck('effective_enabled', 'identifier')
        ->sortKeys()
        ->all();

    return $listed;
}

describe('owners', function (): void {
    it('are enabled by default, also rows written before the column existed', function (): void {
        expect(enabledOwner('acme')->enabled)->toBeTrue()
            ->and(OwnerModel::factory()->create()->enabled)->toBeTrue();

        DB::table(Tables::name('owners'))->insert([
            'id' => (string) Str::ulid(),
            'owner_type_id' => OwnerTypeModel::query()->value('id'),
            'code' => 'legacy',
            'slug' => 'legacy',
            'path' => '/',
            'depth' => 0,
        ]);

        expect(enabledOwnerByCode('legacy')->enabled)->toBeTrue();
    });

    it('take enabled on create and update, and keep it when it is left out', function (): void {
        $owner = enabledOwner('acme', ['enabled' => false]);

        expect($owner->enabled)->toBeFalse();

        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['labels' => ['en' => 'Acme']]));

        expect(enabledOwnerByCode('acme')->enabled)->toBeFalse();

        app(UpdateOwnerAction::class)->execute($owner, UpdateOwnerData::from(['enabled' => true]));

        expect(enabledOwnerByCode('acme')->enabled)->toBeTrue();
    });

    it('refuses an enabled that is not a boolean', function (): void {
        $this->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor', 'enabled' => 'sometimes'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['enabled']);
    });

    it('return enabled from the API, and change it there', function (): void {
        $id = $this->postJson(route('showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor'])
            ->assertCreated()
            ->assertJsonPath('data.enabled', true)
            ->json('data.id');

        $this->patchJson(route('showroom.owners.update', ['owner' => $id]), ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);

        $this->getJson(route('showroom.owners.show', ['owner' => $id]))->assertOk()->assertJsonPath('data.enabled', false);
        $this->getJson(route('showroom.owners.index'))->assertOk()->assertJsonPath('data.0.enabled', false);
    });

    it('take and return enabled over MCP', function (): void {
        mcpTool(CreateOwnerTool::class, ['code' => 'acme', 'type' => 'vendor', 'enabled' => false])->assertOk()->assertSee('"enabled":false');

        expect(enabledOwnerByCode('acme')->enabled)->toBeFalse();

        mcpTool(UpdateOwnerTool::class, ['owner' => enabledOwnerByCode('acme')->id, 'enabled' => true])->assertOk()->assertSee('"enabled":true');

        expect(enabledOwnerByCode('acme')->enabled)->toBeTrue();
    });

    it('queue a sync of every product beneath when enabled changes, and nothing when it is sent unchanged', function (): void {
        enabledOwner('acme');
        $series = enabledOwner('acme-tools', ['parent' => 'acme']);

        Queue::fake([QueueProductSyncs::class]);

        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['enabled' => true, 'labels' => ['en' => 'Tools']]));

        Queue::assertNotPushed(QueueProductSyncs::class);

        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['enabled' => false]));

        Queue::assertPushed(QueueProductSyncs::class, 1);
        Queue::assertPushed(QueueProductSyncs::class, fn (QueueProductSyncs $job): bool => $job->scope === SyncScope::Owner && $job->id === $series->id);

        // A move and an enabled change in one update queue one sync.
        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['enabled' => true, 'parent' => null]));

        Queue::assertPushed(QueueProductSyncs::class, 2);
    });
});

describe('products', function (): void {
    beforeEach(function (): void {
        Catalog::apparel();

        enabledOwner('acme');
        enabledOwner('acme-tools', ['parent' => 'acme']);
        enabledOwner('globex');

        $this->postJson(route('showroom.products.store'), ['identifier' => 'TOOL', 'owner' => 'acme-tools'])->assertCreated();
        $this->postJson(route('showroom.products.store'), ['identifier' => 'OFF', 'owner' => 'globex', 'enabled' => false])->assertCreated();
        $this->postJson(route('showroom.product-models.store'), ['code' => 'polo', 'family_variant' => 'shirts_by_color_size', 'owner' => 'acme'])->assertCreated();
        $this->postJson(route('showroom.product-models.store'), ['code' => 'polo-red', 'parent' => 'polo', 'values' => ['color' => Catalog::value('red')]])->assertCreated();
        $this->postJson(route('showroom.products.store'), ['identifier' => 'VARIANT', 'parent' => 'polo-red', 'values' => ['size' => Catalog::value('m')]])->assertCreated();
    });

    it('return effective_enabled from the listing, following every owner of the chain', function (): void {
        expect(listedEffectiveEnabled())->toBe(['OFF' => false, 'TOOL' => true, 'VARIANT' => true]);

        app(UpdateOwnerAction::class)->execute(enabledOwnerByCode('acme'), UpdateOwnerData::from(['enabled' => false]));

        // The grandchild's product and the variant (its root model's owner) follow; their own flag stays.
        expect(listedEffectiveEnabled())->toBe(['OFF' => false, 'TOOL' => false, 'VARIANT' => false])
            ->and(ProductModel::query()->where('identifier', 'TOOL')->value('enabled'))->toBeTruthy();
    });

    it('return effective_enabled from show, create and update, and over MCP', function (): void {
        app(UpdateOwnerAction::class)->execute(enabledOwnerByCode('acme-tools'), UpdateOwnerData::from(['enabled' => false]));

        $tool = ProductModel::query()->where('identifier', 'TOOL')->sole();

        $this->getJson(route('showroom.products.show', $tool))->assertOk()->assertJsonPath('data.effective_enabled', false)->assertJsonPath('data.enabled', true);
        $this->patchJson(route('showroom.products.update', $tool), ['enabled' => true])->assertOk()->assertJsonPath('data.effective_enabled', false);
        $this->postJson(route('showroom.products.store'), ['identifier' => 'NEW', 'owner' => 'globex'])->assertCreated()->assertJsonPath('data.effective_enabled', true);

        mcpTool(ShowProductTool::class, ['product' => $tool->id])->assertOk()->assertSee('"effective_enabled":false');

        mcpTool(ListProductsTool::class, ['effective_enabled' => true, 'per_page' => 100])->assertOk()
            ->assertSee(['"identifier":"NEW"', '"identifier":"VARIANT"', '"effective_enabled":true'])
            ->assertDontSee(['"identifier":"TOOL"', '"identifier":"OFF"', '"effective_enabled":false']);
    });

    it('refuses effective_enabled sent on create or update, as it is read-only', function (): void {
        $this->postJson(route('showroom.products.store'), ['identifier' => 'CAP', 'effective_enabled' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['effective_enabled']);

        $this->patchJson(route('showroom.products.update', ProductModel::query()->where('identifier', 'TOOL')->sole()), ['effective_enabled' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['effective_enabled']);
    });
});
