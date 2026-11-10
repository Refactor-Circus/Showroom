<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Carbon;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Actions\UpdateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Data\UpdateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\ShowroomServiceProvider;
use RefactorCircus\Showroom\Tests\Fixtures\ReadOnlyFlagPolicy;
use Workbench\App\Models\User;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');
});

/**
 * An owner by its code; pages address it by its lookup key.
 */
function uiOwner(string $code): OwnerModel
{
    return OwnerModel::query()->where('code', $code)->firstOrFail();
}

/**
 * The flags of the owner page tests, a vendor carrying one and a series
 * beneath it with an own flag and an external id.
 */
function uiFlaggedSeries(): OwnerModel
{
    FlagModel::factory()->create(['code' => 'pricing_only', 'sort_order' => 1]);
    FlagModel::factory()->create(['code' => 'restricted', 'sort_order' => 2]);
    FlagModel::factory()->create(['code' => 'clearance', 'applies_to' => ['product'], 'sort_order' => 3]);
    FlagModel::factory()->create(['code' => 'preferred', 'applies_to' => ['owner'], 'sort_order' => 4]);

    OwnerTypeModel::factory()->create(['code' => 'vendor']);

    app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => 'acme', 'type' => 'vendor', 'flags' => ['pricing_only']]));

    return app(CreateOwnerAction::class)->execute(CreateOwnerData::from([
        'code' => 'acme-tools',
        'type' => 'vendor',
        'parent' => 'acme',
        'labels' => ['en' => 'Acme Tools'],
        'flags' => ['preferred'],
        'external_ids' => ['erp' => 'V-1'],
    ]));
}

/**
 * Turn authorization on with a flag policy refusing `assign`, and sign in.
 */
function uiSignInOwnerEditorWithoutAssign(): void
{
    config()->set('showroom.authorization', true);
    config()->set('showroom.policies.'.FlagModel::class, ReadOnlyFlagPolicy::class);

    (fn () => $this->registerPolicies())->call(app()->getProvider(ShowroomServiceProvider::class));

    test()->actingAs(User::forceCreate(['name' => 'Ann', 'email' => 'ann@example.test', 'password' => 'x']));
}

it('defines owner types and their chain rules', function (): void {
    $this->get(route('atrium.showroom.owner-types.index'))->assertOk()->assertSee(__('showroom::showroom.no_owner_types'));

    $this->post(route('atrium.showroom.owner-types.store'), ['code' => 'vendor', 'any_parent' => '1', 'can_be_root' => '1', 'owns_products' => '1'])
        ->assertRedirect(route('atrium.showroom.owner-types.show', 'vendor'));

    $this->post(route('atrium.showroom.owner-types.store'), [
        'code' => 'series',
        'any_parent' => '0',
        'parent_types' => ['vendor'],
        'can_be_root' => '0',
        'owns_products' => '1',
    ])->assertRedirect();

    $series = OwnerTypeModel::query()->with('parentTypes')->where('code', 'series')->firstOrFail();

    expect($series->restricts_parents)->toBeTrue()
        ->and($series->can_be_root)->toBeFalse()
        ->and($series->parentTypes->pluck('code')->all())->toBe(['vendor']);

    $this->get(route('atrium.showroom.owner-types.index'))->assertOk()->assertSee('series');

    $this->patch(route('atrium.showroom.owner-types.update', 'series'), ['any_parent' => '1', 'can_be_root' => '1', 'owns_products' => '0'])->assertRedirect();

    expect($series->fresh()?->restricts_parents)->toBeFalse();
});

it('builds, moves and browses an ownership chain', function (): void {
    OwnerTypeModel::factory()->create(['code' => 'vendor']);

    $this->post(route('atrium.showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor', 'labels' => ['en' => 'Acme']])
        ->assertRedirect(route('atrium.showroom.owners.show', uiOwner('acme')));

    $this->post(route('atrium.showroom.owners.store'), ['code' => 'acme-eu', 'type' => 'vendor', 'parent' => 'acme'])->assertRedirect();
    $this->post(route('atrium.showroom.owners.store'), ['code' => 'initech', 'type' => 'vendor'])->assertRedirect();

    $this->get(route('atrium.showroom.owners.show', uiOwner('acme-eu')))
        ->assertOk()
        ->assertSee('data-testid="owner-chain"', false)
        ->assertSeeInOrder(['Acme', 'acme-eu']);

    $this->patch(route('atrium.showroom.owners.update', uiOwner('acme-eu')), ['parent' => 'initech'])->assertSessionHasNoErrors();

    expect(uiOwner('acme-eu')->parent?->code)->toBe('initech');

    $this->get(route('atrium.showroom.owners.index', ['type' => 'vendor']))->assertOk()->assertSee('acme-eu');

    $this->from(route('atrium.showroom.owners.show', uiOwner('initech')))
        ->delete(route('atrium.showroom.owners.destroy', uiOwner('initech')))
        ->assertSessionHasErrors('owner');
});

it('assigns a product to an owner from its page', function (): void {
    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    $this->post(route('atrium.showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor']);
    $product = ProductModel::factory()->create(['identifier' => 'TEE-1']);

    $this->patch(route('atrium.showroom.products.update', $product), ['owner' => 'acme'])->assertSessionHasNoErrors();

    expect(ProductModel::query()->firstOrFail()->owner?->code)->toBe('acme');

    $this->get(route('atrium.showroom.products.index', ['owner' => 'acme']))->assertOk()->assertSee('TEE-1');
    $this->get(route('atrium.showroom.owners.show', uiOwner('acme')))->assertOk()->assertSee('1 product(s)', false);
});

describe('slug, external ids and flags', function (): void {
    it('shows the slug read-only', function (): void {
        $series = uiFlaggedSeries();

        $this->get(route('atrium.showroom.owners.show', $series))
            ->assertOk()
            ->assertSee('data-testid="owner-identity"', false)
            ->assertSeeInOrder(['data-testid="owner-slug"', 'acme-tools'], false)
            ->assertDontSee('name="slug"', false);
    });

    it('edits the external ids as a map: change, add and remove a source', function (): void {
        $series = uiFlaggedSeries();

        $this->get(route('atrium.showroom.owners.show', $series))
            ->assertOk()
            ->assertSee('data-testid="external-ids"', false)
            ->assertSee('data-external-source="erp"', false)
            ->assertSee('name="external_ids[erp]"', false)
            ->assertSee('value="V-1"', false)
            ->assertSee(__('showroom::showroom.external_source'))
            ->assertSee(__('showroom::showroom.external_id'))
            ->assertSee('name="external_ids_new[source]"', false)
            ->assertSee('name="external_ids_new[id]"', false)
            ->assertDontSee('name="external_ids_new[system]"', false);

        $this->patch(route('atrium.showroom.owners.update', $series), [
            'external_ids' => ['erp' => 'V-2'],
            'external_ids_new' => ['source' => 'd365', 'id' => 'D-1'],
        ])->assertSessionHasNoErrors();

        expect(ExternalIds::present(uiOwner('acme-tools')->externalIds))->toBe(['d365' => 'D-1', 'erp' => 'V-2']);

        $this->patch(route('atrium.showroom.owners.update', $series), [
            'external_ids' => ['d365' => '', 'erp' => 'V-2'],
        ])->assertSessionHasNoErrors();

        expect(ExternalIds::present(uiOwner('acme-tools')->externalIds))->toBe(['erp' => 'V-2']);
    });

    it('refuses a malformed new source at external_ids', function (): void {
        $series = uiFlaggedSeries();

        $this->from(route('atrium.showroom.owners.show', $series))
            ->patch(route('atrium.showroom.owners.update', $series), ['external_ids_new' => ['source' => 'Not Valid', 'id' => 'X']])
            ->assertSessionHasErrors('external_ids');

        expect(ExternalIds::present(uiOwner('acme-tools')->externalIds))->toBe(['erp' => 'V-1']);
    });

    it('shows when each source last synced, read-only', function (): void {
        $this->travelTo(Carbon::parse('2026-11-01 09:00:00', 'UTC'));
        $series = uiFlaggedSeries();
        $this->travelBack();

        app(UpdateOwnerAction::class)->execute($series, UpdateOwnerData::from(['external_ids' => ['d365' => 'D-1']]));
        $series->externalIds()->where('source', 'd365')->toBase()->update(['synced_at' => null]);

        $html = (string) $this->get(route('atrium.showroom.owners.show', $series))->assertOk()->getContent();

        expect($html)
            ->toMatch('/data-external-synced="erp"[^>]*datetime="2026-11-01T09:00:00\+00:00"/')
            ->toMatch('/data-external-synced="d365"[^>]*>\s*'.preg_quote(__('showroom::showroom.external_never_synced'), '/').'/')
            ->not->toContain('name="external_ids_synced_at')
            ->not->toContain('name="synced_at');
    });

    it('offers as own flags only the flags that apply to owners, with the own ones ticked', function (): void {
        $series = uiFlaggedSeries();

        $html = (string) $this->get(route('atrium.showroom.owners.show', $series))
            ->assertOk()
            ->assertSee('data-testid="own-flags"', false)
            ->getContent();

        expect($html)->toContain('id="atrium-flags-pricing_only"')
            ->toContain('id="atrium-flags-restricted"')
            ->toContain('id="atrium-flags-preferred"')
            ->not->toContain('id="atrium-flags-clearance"')
            ->and(preg_match('/id="atrium-flags-preferred"[^>]*value="preferred"\s+checked\s/', $html))->toBe(1)
            ->and(preg_match('/id="atrium-flags-pricing_only"[^>]*value="pricing_only"\s+checked\s/', $html))->toBe(0);
    });

    it('shows the effective flags read-only, marking those inherited from the owners above', function (): void {
        $series = uiFlaggedSeries();

        $this->get(route('atrium.showroom.owners.show', $series))
            ->assertOk()
            ->assertSee('data-testid="effective-flags"', false)
            ->assertSee('data-effective-flag="pricing_only" data-inherited="true"', false)
            ->assertSee('data-effective-flag="preferred" data-inherited="false"', false)
            ->assertDontSee('data-effective-flag="restricted"', false);
    });

    it('replaces the own flags from the page, and clears them when none is ticked', function (): void {
        $series = uiFlaggedSeries();

        $this->patch(route('atrium.showroom.owners.update', $series), ['flags_sent' => '1', 'flags' => ['restricted']])
            ->assertSessionHasNoErrors();

        expect(uiOwner('acme-tools')->flags()->pluck('code')->all())->toBe(['restricted']);

        $this->patch(route('atrium.showroom.owners.update', $series), ['flags_sent' => '1'])->assertSessionHasNoErrors();

        expect(uiOwner('acme-tools')->flags()->count())->toBe(0);
    });

    it('hides the own-flags field without assign, and still shows the effective flags', function (): void {
        $series = uiFlaggedSeries();
        uiSignInOwnerEditorWithoutAssign();

        $this->get(route('atrium.showroom.owners.show', $series))
            ->assertOk()
            ->assertDontSee('data-testid="own-flags"', false)
            ->assertDontSee('name="flags[]"', false)
            ->assertDontSee('name="flags_sent"', false)
            ->assertSee('data-effective-flag="preferred" data-inherited="false"', false)
            ->assertSee('data-effective-flag="pricing_only" data-inherited="true"', false)
            ->assertSee('name="external_ids[erp]"', false);

        // The rest of the page still saves; a flag change is refused.
        $this->patch(route('atrium.showroom.owners.update', $series), ['labels' => ['en' => 'Acme Tooling']])->assertSessionHasNoErrors();
        $this->patch(route('atrium.showroom.owners.update', $series), ['flags_sent' => '1', 'flags' => ['restricted']])->assertForbidden();

        expect(uiOwner('acme-tools')->flags()->pluck('code')->all())->toBe(['preferred'])
            ->and(uiOwner('acme-tools')->labels)->toBe(['en' => 'Acme Tooling']);
    });
});

describe('links', function (): void {
    it('links owners by id by default', function (): void {
        $series = uiFlaggedSeries();

        $this->get(route('atrium.showroom.owners.index'))
            ->assertOk()
            ->assertSee('href="'.route('atrium.showroom.owners.show', $series->id).'"', false);
    });

    it('links owners by slug when the lookup key is slug', function (): void {
        uiFlaggedSeries();
        config()->set('showroom.lookup.owners', 'slug');

        $this->get(route('atrium.showroom.owners.index'))
            ->assertOk()
            ->assertSee('href="'.route('atrium.showroom.owners.show', 'acme-tools').'"', false);

        $this->get(route('atrium.showroom.owners.show', 'acme-tools'))
            ->assertOk()
            ->assertSee('action="'.route('atrium.showroom.owners.update', 'acme-tools').'"', false)
            ->assertSee('href="'.route('atrium.showroom.owners.show', 'acme').'"', false);
    });
});
