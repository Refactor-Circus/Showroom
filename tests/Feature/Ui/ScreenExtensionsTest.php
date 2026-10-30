<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Showroom\Atrium\ShowroomPlugin;
use RefactorCircus\Showroom\Atrium\Support\Screens;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use Symfony\Component\Finder\SplFileInfo;
use Workbench\App\Models\User;

/*
 * Every Showroom screen declares itself on Atrium's screen extensions
 * registry: its key, kind, record and the standard slots, resolved only after
 * the screen's own check, and laid out the same way on every page.
 */

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');

    screenExtGrants([]);
    screenExtMarkerView();
});

/**
 * A view rendering who it renders for: the marker (the screen key by
 * default), the screen, and the record or row when there is one.
 */
function screenExtMarkerView(): string
{
    $directory = sys_get_temp_dir().'/showroom-screen-extensions-'.getmypid();
    File::ensureDirectoryExists($directory);
    File::put($directory.'/marker.blade.php', '<span>{{ $marker ?? \'marker\' }} on {{ $screen->key }}@isset($record) for {{ $record->getKey() }}@endisset</span>');

    View::addNamespace('screen-ext', $directory);

    return 'screen-ext::marker';
}

/**
 * The abilities each test user holds, as "ability Entity" by user key.
 *
 * @param  array<int|string, array<int, string>>|null  $set
 * @return array<int|string, array<int, string>>
 */
function screenExtGrants(?array $set = null): array
{
    static $grants = [];

    if ($set !== null) {
        $grants = $set;
    }

    return $grants;
}

/**
 * A signed-in user holding exactly these abilities on Showroom's models.
 *
 * @param  array<int, string>  $grants
 */
function screenExtUser(array $grants = []): User
{
    $user = User::forceCreate(['name' => 'Someone', 'email' => fake()->unique()->safeEmail(), 'password' => 'x']);

    screenExtGrants([...screenExtGrants(), $user->getKey() => $grants]);

    return $user;
}

/**
 * Turn authorization on and answer Showroom's policy abilities from the
 * users' grants, recording every subject an ability is asked about. Other
 * abilities, such as an extension's own, are left to the Gate.
 *
 * @param  list<string>  $subjects
 */
function screenExtAuthorize(array &$subjects = []): void
{
    config()->set('showroom.authorization', true);

    Gate::before(function (mixed $user, string $ability, array $arguments) use (&$subjects): ?bool {
        $subject = $arguments[0] ?? null;
        $subjects[] = $ability.' '.get_debug_type($subject).(is_string($subject) ? ':'.$subject : '');

        $class = $subject instanceof Model ? $subject::class : $subject;

        if (! in_array($ability, ['viewAny', 'view', 'create', 'update', 'delete'], true) || ! is_string($class) || ! str_starts_with($class, 'RefactorCircus\\Showroom\\Domains\\')) {
            return null;
        }

        $entity = Str::replaceEnd('Model', '', class_basename($class));

        return $user instanceof User && in_array($ability.' '.$entity, screenExtGrants()[$user->getKey()] ?? [], true);
    });
}

/**
 * A small catalog with two records on every list, built through the screens
 * while authorization is off.
 */
function screenExtWorld(): void
{
    $test = test();

    Catalog::apparel();

    $test->post(route('atrium.showroom.products.store'), ['identifier' => 'TEE-1', 'family' => 'shirts'])->assertRedirect();
    $test->post(route('atrium.showroom.product-models.store'), ['code' => 'tee', 'family_variant' => 'shirts_by_size'])->assertRedirect();
    $test->post(route('atrium.showroom.product-models.store'), ['code' => 'polo', 'family_variant' => 'shirts_by_color_size'])->assertRedirect();
    $test->post(route('atrium.showroom.products.store'), [
        'identifier' => 'TEE-S',
        'parent' => 'tee',
        'values' => ['size' => [['locale' => '', 'scope' => '', 'data' => 's']]],
    ])->assertSessionHasNoErrors();

    foreach (['related', 'upsell'] as $code) {
        $test->post(route('atrium.showroom.association-types.store'), ['code' => $code])->assertRedirect();
    }

    foreach (['hero', 'spare'] as $code) {
        $test->post(route('atrium.showroom.assets.store'), ['code' => $code, 'file' => UploadedFile::fake()->image($code.'.jpg')])->assertRedirect();
    }

    foreach (['master', 'outlet'] as $code) {
        $test->post(route('atrium.showroom.categories.store'), ['code' => $code])->assertRedirect();
    }

    foreach (['vendor', 'brand'] as $code) {
        $test->post(route('atrium.showroom.owner-types.store'), ['code' => $code, 'any_parent' => '1', 'can_be_root' => '1', 'owns_products' => '1'])->assertRedirect();
    }

    foreach (['acme', 'initech'] as $code) {
        $test->post(route('atrium.showroom.owners.store'), ['code' => $code, 'type' => 'vendor'])->assertRedirect();
    }

    foreach (['basics', 'extras'] as $code) {
        $test->post(route('atrium.showroom.attribute-groups.store'), ['code' => $code])->assertRedirect();
    }

    $test->post(route('atrium.showroom.families.store'), ['code' => 'pants'])->assertRedirect();

    // Two transfer runs, for the transfers list.
    foreach (['UI-1', 'UI-2'] as $identifier) {
        $file = UploadedFile::fake()->createWithContent('products.jsonl', json_encode(['identifier' => $identifier])."\n");
        $test->post(route('atrium.showroom.transfers.import'), ['file' => $file, 'mode' => 'upsert'])->assertSessionHasNoErrors();
    }

    expect(RunModel::query()->count())->toBe(2);
}

/**
 * GET a screen as a fresh request, so nothing an earlier request remembered
 * about extensions carries over.
 */
function screenExtGet(string $route, ?string $parameter, array $query = []): TestResponse
{
    app()->forgetScopedInstances();

    return test()->get(route($route, array_filter([$parameter, ...$query], fn (mixed $value): bool => $value !== null)));
}

function screenExtTestid(string $id): string
{
    return 'data-testid="'.$id.'"';
}

function screenExtLink(string $resource, string $key): string
{
    return 'href="'.route('atrium.showroom.'.$resource.'.show', $key).'"';
}

/**
 * Register a public tab, a public section in every standard slot and in an
 * undeclared one, and a row section whose closures record the rows they get.
 *
 * @param  list<Model|null>  $rows
 */
function screenExtRegister(string $key, array &$rows): void
{
    $marker = screenExtMarkerView();

    Atrium::screens()->tab($key, TabDefinition::make('acme.tab')->label('Acme')->view($marker)->public()
        ->resolve(fn (): array => ['marker' => 'the acme panel']));

    foreach (['header.actions', 'before', 'aside', 'after', 'history'] as $slot) {
        Atrium::screens()->section($key, $slot, SectionDefinition::make('acme.'.$slot)->view($marker)->public()
            ->resolve(fn (): array => ['marker' => 'in '.$slot]));
    }

    Atrium::screens()->section($key, 'row.actions', SectionDefinition::make('acme.row.actions')->view($marker)
        ->authorize(function (Request $request, ?Model $record) use (&$rows): bool {
            $rows[] = $record;

            return true;
        })
        ->resolve(fn (): array => ['marker' => 'in row.actions']));
}

/**
 * Pages with a side column put `aside` in it, by the column's test id.
 */
const SCREEN_EXT_SIDE_COLUMNS = ['showroom.asset' => 'asset-side-column'];

dataset('showroom screens', [
    'products' => ['showroom.products', 'atrium.showroom.products.index', null, ProductModel::class, fn (): string => screenExtLink('products', 'TEE-1')],
    'product create' => ['showroom.product-create', 'atrium.showroom.products.create', null, null, fn (): string => screenExtTestid('create-product')],
    'product' => ['showroom.product', 'atrium.showroom.products.show', 'TEE-1', null, fn (): string => screenExtTestid('save-product')],
    'product models' => ['showroom.product-models', 'atrium.showroom.product-models.index', null, ProductModelModel::class, fn (): string => screenExtLink('product-models', 'tee')],
    'product model' => ['showroom.product-model', 'atrium.showroom.product-models.show', 'tee', null, fn (): string => screenExtTestid('save-product-model')],
    'channels' => ['showroom.channels', 'atrium.showroom.channels.index', null, ChannelModel::class, fn (): string => screenExtLink('channels', 'ecommerce')],
    'channel' => ['showroom.channel', 'atrium.showroom.channels.show', 'ecommerce', null, fn (): string => screenExtTestid('channel-details-card')],
    'assets' => ['showroom.assets', 'atrium.showroom.assets.index', null, AssetModel::class, fn (): string => screenExtLink('assets', 'hero')],
    'asset' => ['showroom.asset', 'atrium.showroom.assets.show', 'hero', null, fn (): string => screenExtTestid('asset-details-card')],
    'categories' => ['showroom.categories', 'atrium.showroom.categories.index', null, CategoryModel::class, fn (): string => screenExtLink('categories', 'master')],
    'category' => ['showroom.category', 'atrium.showroom.categories.show', 'master', null, fn (): string => screenExtTestid('category-details-card')],
    'owner types' => ['showroom.owner-types', 'atrium.showroom.owner-types.index', null, OwnerTypeModel::class, fn (): string => screenExtLink('owner-types', 'vendor')],
    'owner type' => ['showroom.owner-type', 'atrium.showroom.owner-types.show', 'vendor', null, fn (): string => screenExtTestid('owner-type-details-card')],
    'owners' => ['showroom.owners', 'atrium.showroom.owners.index', null, OwnerModel::class, fn (): string => screenExtLink('owners', 'acme')],
    'owner' => ['showroom.owner', 'atrium.showroom.owners.show', 'acme', null, fn (): string => screenExtTestid('owner-details-card')],
    'families' => ['showroom.families', 'atrium.showroom.families.index', null, FamilyModel::class, fn (): string => screenExtLink('families', 'shirts')],
    'family' => ['showroom.family', 'atrium.showroom.families.show', 'shirts', null, fn (): string => screenExtTestid('save-family')],
    'family variant' => ['showroom.family-variant', 'atrium.showroom.family-variants.show', 'shirts_by_size', null, fn (): string => screenExtLink('product-models', 'tee')],
    'attributes' => ['showroom.attributes', 'atrium.showroom.attributes.index', null, AttributeModel::class, fn (): string => screenExtLink('attributes', 'color')],
    'attribute create' => ['showroom.attribute-create', 'atrium.showroom.attributes.create', null, null, fn (): string => screenExtTestid('create-attribute')],
    'attribute' => ['showroom.attribute', 'atrium.showroom.attributes.show', 'color', null, fn (): string => screenExtTestid('save-attribute')],
    'attribute groups' => ['showroom.attribute-groups', 'atrium.showroom.attribute-groups.index', null, AttributeGroupModel::class, fn (): string => screenExtLink('attribute-groups', 'basics')],
    'attribute group' => ['showroom.attribute-group', 'atrium.showroom.attribute-groups.show', 'basics', null, fn (): string => screenExtTestid('group-details-card')],
    'association types' => ['showroom.association-types', 'atrium.showroom.association-types.index', null, AssociationTypeModel::class, fn (): string => screenExtTestid('delete-association-type')],
    'transfers' => ['showroom.transfers', 'atrium.showroom.transfers.index', null, RunModel::class, fn (): string => 'data-run="showroom:import-products"'],
]);

it('declares every screen with its key, kind, record and standard slots, and renders a section in each', function (string $key, string $route, ?string $parameter, ?string $row, Closure $body): void {
    screenExtWorld();

    $rows = [];
    screenExtRegister($key, $rows);

    $response = screenExtGet($route, $parameter)->assertOk();
    $html = (string) $response->getContent();
    $resolved = $response->viewData('resolved');
    $kind = $row === null ? Screen::DETAIL : Screen::LIST;
    $pageRecord = $resolved->screen->record;
    $recordField = $pageRecord instanceof ProductModel ? 'identifier' : 'code';

    // The declaration: key, kind, record (the page's own, found by its code)
    // and Screen::standard()'s slots.
    expect($resolved->screen->key)->toBe($key)
        ->and($resolved->screen->kind)->toBe($kind)
        ->and($pageRecord?->getAttribute($recordField))->toBe($parameter)
        ->and($resolved->screen->slots)->toBe($row === null ? ['header.actions', 'before', 'after', 'aside'] : ['header.actions', 'before', 'after'])
        ->and($resolved->screen->rowSlots)->toBe($row === null ? [] : ['row.actions'])
        ->and(array_map(fn (TabDefinition $tab): string => $tab->key, $resolved->tabs))->toBe(['overview', 'acme.tab'])
        ->and($resolved->openTab)->toBe('overview')
        ->and($resolved->openRegisteredTab)->toBeNull();

    // Each section of a declared slot once; none of an undeclared one.
    foreach ($row === null ? ['header.actions', 'before', 'aside', 'after'] : ['header.actions', 'before', 'after'] as $slot) {
        expect(substr_count($html, screenExtTestid('section-acme.'.$slot)))->toBe(1);
    }

    expect($html)->not->toContain(screenExtTestid('section-acme.history'))
        ->not->toContain('in history');

    $response->assertSee(screenExtTestid('tab-overview'), false)
        ->assertSee(screenExtTestid('tab-acme.tab'), false)
        ->assertDontSee('data-testid="tab-panel-', false);

    if ($row === null) {
        expect($html)->not->toContain(screenExtTestid('section-acme.row.actions'))
            ->and($rows)->toBe([]);
    } else {
        // Once per row, each row its own record.
        $keys = array_map(fn (?Model $record): mixed => $record?->getKey(), $rows);

        expect($html)->not->toContain(screenExtTestid('section-acme.aside'))
            ->and(count($rows))->toBeGreaterThanOrEqual(2)
            ->and(array_unique($keys))->toHaveCount(count($rows))
            ->and(substr_count($html, screenExtTestid('section-acme.row.actions')))->toBe(count($rows));

        foreach ($rows as $record) {
            expect($record)->toBeInstanceOf($row)
                ->and($html)->toContain('in row.actions on '.$key.' for '.$record->getKey());
        }
    }

    // The header's actions, the tab bar under the header, then `before`, the
    // page, `aside` (in the side column, or just before `after`) and `after`.
    $side = SCREEN_EXT_SIDE_COLUMNS[$key] ?? null;

    $response->assertSeeInOrder([
        '<h1',
        screenExtTestid('section-acme.header.actions'),
        screenExtTestid('screen-tabs'),
        screenExtTestid('section-acme.before'),
        ...($side !== null ? [screenExtTestid($side), screenExtTestid('section-acme.aside')] : []),
        $body(),
        ...($row !== null ? [screenExtTestid('section-acme.row.actions')] : []),
        ...($row === null && $side === null ? [screenExtTestid('section-acme.aside')] : []),
        screenExtTestid('section-acme.after'),
    ], false);
})->with('showroom screens');

it('opens a registered tab through ?tab= in place of the page, keeping only the header actions', function (string $key, string $route, ?string $parameter, ?string $row, Closure $body): void {
    screenExtWorld();

    $rows = [];
    screenExtRegister($key, $rows);

    $response = screenExtGet($route, $parameter, ['tab' => 'acme.tab'])
        ->assertOk()
        ->assertSee(screenExtTestid('tab-panel-acme.tab'), false)
        ->assertSee('the acme panel on '.$key)
        ->assertSee(screenExtTestid('section-acme.header.actions'), false)
        ->assertDontSee($body(), false);

    foreach (['before', 'aside', 'after', 'row.actions'] as $slot) {
        $response->assertDontSee(screenExtTestid('section-acme.'.$slot), false);
    }

    $response->assertSeeInOrder([
        screenExtTestid('section-acme.header.actions'),
        screenExtTestid('screen-tabs'),
        screenExtTestid('tab-panel-acme.tab'),
    ], false);

    expect($response->viewData('resolved')->openTab)->toBe('acme.tab')
        ->and($rows)->toBe([]);
})->with('showroom screens');

it('refuses a denied user 403 and a guest 401 before any extension is asked or resolved', function (string $key, string $route, ?string $parameter, ?string $row, Closure $body): void {
    screenExtWorld();
    screenExtAuthorize();

    $calls = 0;
    $count = function () use (&$calls): bool {
        $calls++;

        return true;
    };
    $resolve = function () use (&$calls): array {
        $calls++;

        return [];
    };
    $marker = screenExtMarkerView();

    Atrium::screens()->tab($key, TabDefinition::make('acme.tab')->view($marker)->authorize($count)->resolve($resolve));
    Atrium::screens()->tab('*', TabDefinition::make('acme.everywhere')->view($marker)->authorize($count)->resolve($resolve));

    foreach (['header.actions', 'before', 'aside', 'after', 'row.actions'] as $slot) {
        Atrium::screens()->section($key, $slot, SectionDefinition::make('acme.'.$slot)->view($marker)->authorize($count)->resolve($resolve));
    }

    screenExtGet($route, $parameter)->assertUnauthorized();

    $this->actingAs(screenExtUser());
    screenExtGet($route, $parameter)->assertForbidden();
    screenExtGet($route, $parameter, ['tab' => 'acme.tab'])->assertForbidden();

    expect($calls)->toBe(0);
})->with('showroom screens');

it('renders nothing extra while nothing is registered', function (string $key, string $route, ?string $parameter, ?string $row, Closure $body): void {
    screenExtWorld();

    $html = (string) screenExtGet($route, $parameter)->assertOk()->assertSee($body(), false)->getContent();

    expect($html)
        ->not->toContain(screenExtTestid('screen-tabs'))
        ->not->toContain('data-testid="tab-')
        ->not->toContain('data-testid="section-');
})->with('showroom screens');

it('puts row.actions in a trailing cell under an empty heading on every table list', function (string $key, string $route): void {
    screenExtWorld();

    $rows = [];
    screenExtRegister($key, $rows);

    $html = (string) screenExtGet($route, null)->assertOk()->getContent();

    $trailing = preg_match_all('/data-testid="section-acme\.row\.actions"[^>]*>\s*<span>[^<]*<\/span>\s*<\/div>\s*<\/td>\s*<\/tr>/', $html);

    expect(count($rows))->toBeGreaterThanOrEqual(2)
        ->and($trailing)->toBe(count($rows))
        ->and(preg_match('/<th[^>]*>\s*<\/th>\s*<\/tr>/', $html))->toBe(1);
})->with([
    'products' => ['showroom.products', 'atrium.showroom.products.index'],
    'product models' => ['showroom.product-models', 'atrium.showroom.product-models.index'],
    'channels' => ['showroom.channels', 'atrium.showroom.channels.index'],
    'categories' => ['showroom.categories', 'atrium.showroom.categories.index'],
    'owner types' => ['showroom.owner-types', 'atrium.showroom.owner-types.index'],
    'owners' => ['showroom.owners', 'atrium.showroom.owners.index'],
    'families' => ['showroom.families', 'atrium.showroom.families.index'],
    'attributes' => ['showroom.attributes', 'atrium.showroom.attributes.index'],
    'attribute groups' => ['showroom.attribute-groups', 'atrium.showroom.attribute-groups.index'],
    'association types' => ['showroom.association-types', 'atrium.showroom.association-types.index'],
    'transfers' => ['showroom.transfers', 'atrium.showroom.transfers.index'],
]);

it('puts row.actions at the bottom of each asset tile', function (): void {
    screenExtWorld();

    $rows = [];
    screenExtRegister('showroom.assets', $rows);

    $html = (string) screenExtGet('atrium.showroom.assets.index', null)->assertOk()->getContent();

    // After the tile's link, inside the tile.
    $tiles = preg_match_all('/<\/a>\s*<div[^>]*data-testid="section-acme\.row\.actions"[^>]*>\s*<span>[^<]*<\/span>\s*<\/div>\s*<\/div>/', $html);

    expect(count($rows))->toBeGreaterThanOrEqual(2)
        ->and($tiles)->toBe(count($rows))
        ->and(substr_count($html, 'data-asset="'))->toBe(count($rows));
});

it('puts aside in the asset page\'s side column and keeps before and after across its grid', function (): void {
    screenExtWorld();

    $rows = [];
    screenExtRegister('showroom.asset', $rows);

    $html = (string) screenExtGet('atrium.showroom.assets.show', 'hero')->assertOk()->getContent();

    $column = Str::between($html, screenExtTestid('asset-side-column'), 'lg:col-span-2');

    preg_match('/<div[^>]*data-testid="section-acme\.before"[^>]*>/', $html, $before);
    preg_match('/<div[^>]*data-testid="section-acme\.after"[^>]*>/', $html, $after);

    expect($column)->toContain(screenExtTestid('section-acme.aside'))
        ->and($before[0] ?? '')->toContain('lg:col-span-3')
        ->and($after[0] ?? '')->toContain('lg:col-span-3');
});

it('shows a permission(\'view\') section on a product to those who may view it', function (): void {
    screenExtWorld();
    screenExtAuthorize();

    Atrium::screens()->section('showroom.product', 'before', SectionDefinition::make('acme.viewers')->view(screenExtMarkerView())->permission('view'));

    $this->actingAs(screenExtUser(['view Product']));
    screenExtGet('atrium.showroom.products.show', 'TEE-1')->assertOk()->assertSee(screenExtTestid('section-acme.viewers'), false);

    // One who may not view the product never opens its page; the screen's
    // access closure denies them the section all the same.
    $product = ProductModel::query()->where('identifier', 'TEE-1')->firstOrFail();
    $denied = screenExtUser(['view ProductModel']);
    $request = Request::create('/');
    $request->setUserResolver(fn (): User => $denied);
    app()->forgetScopedInstances();

    $resolved = app(ScreenExtensions::class)->resolve(Screens::detail('showroom.product', $product, ProductModel::class), $request);

    expect($resolved->sections('before'))->toBe([])
        ->and(Screens::detail('showroom.product', $product, ProductModel::class)->permits('view', $request, $product))->toBeFalse();
});

it('shows a permission(\'view\') section on the product list to those who may view products', function (): void {
    screenExtWorld();
    screenExtAuthorize();

    Atrium::screens()->section('showroom.products', 'before', SectionDefinition::make('acme.viewers')->view(screenExtMarkerView())->permission('view'));

    $this->actingAs(screenExtUser(['viewAny Product', 'view Product']));
    screenExtGet('atrium.showroom.products.index', null)->assertOk()->assertSee(screenExtTestid('section-acme.viewers'), false);

    $this->actingAs(screenExtUser(['viewAny Product']));
    screenExtGet('atrium.showroom.products.index', null)->assertOk()->assertDontSee(screenExtTestid('section-acme.viewers'), false);
});

it('shows another package\'s permission only once the Gate allows it', function (): void {
    screenExtWorld();
    screenExtAuthorize();

    $allowed = false;
    $asked = [];

    Gate::define('acme.extra', function (User $user, mixed ...$arguments) use (&$allowed, &$asked): bool {
        $asked[] = array_map(get_debug_type(...), $arguments);

        return $allowed;
    });

    Atrium::screens()->section('showroom.product', 'before', SectionDefinition::make('acme.extra')->view(screenExtMarkerView())->permission('acme.extra'));

    $this->actingAs(screenExtUser(['view Product']));
    screenExtGet('atrium.showroom.products.show', 'TEE-1')->assertOk()->assertDontSee(screenExtTestid('section-acme.extra'), false);

    $allowed = true;
    screenExtGet('atrium.showroom.products.show', 'TEE-1')->assertOk()->assertSee(screenExtTestid('section-acme.extra'), false);

    // Asked about the product itself, which showroom.policies covers.
    expect($asked)->not->toBeEmpty()
        ->and(array_values(array_unique($asked, SORT_REGULAR)))->toBe([[ProductModel::class]]);
});

it('asks about products for a transfer row, never handing an Impex run to the Authorizer', function (): void {
    screenExtWorld();

    $subjects = [];
    screenExtAuthorize($subjects);
    Exceptions::fake();

    Atrium::screens()->section('showroom.transfers', 'row.actions', SectionDefinition::make('acme.run')->view(screenExtMarkerView())->permission('view'));

    $this->actingAs(screenExtUser(['viewAny Product', 'view Product']));
    $allowed = (string) screenExtGet('atrium.showroom.transfers.index', null)->assertOk()->getContent();

    $this->actingAs(screenExtUser(['viewAny Product']));
    $denied = (string) screenExtGet('atrium.showroom.transfers.index', null)->assertOk()->getContent();

    expect(substr_count($allowed, screenExtTestid('section-acme.run')))->toBe(2)
        ->and($denied)->not->toContain(screenExtTestid('section-acme.run'))
        // Each row asked `view` on the product class, never about the run.
        ->and($subjects)->toContain('view string:'.ProductModel::class)
        ->and($subjects)->not->toContain('view '.RunModel::class);

    Exceptions::assertNothingReported();
});

it('shows every permission() extension while authorization is off', function (): void {
    screenExtWorld();

    Atrium::screens()->section('showroom.product', 'before', SectionDefinition::make('acme.viewers')->view(screenExtMarkerView())->permission('view'));

    screenExtGet('atrium.showroom.products.show', 'TEE-1')->assertOk()->assertSee(screenExtTestid('section-acme.viewers'), false);
});

it('leaves the audit trail to the audit log: no view names it, and no navigation item links a history', function (): void {
    $views = collect(File::allFiles(dirname(__DIR__, 3).'/resources/views'))
        ->filter(fn (SplFileInfo $file): bool => str_contains((string) file_get_contents($file->getPathname()), 'x-atrium::audit'))
        ->map(fn (SplFileInfo $file): string => $file->getRelativePathname())
        ->values()
        ->all();

    $urls = array_map(fn (NavItem $item): string => (string) $item->resolveUrl(), app(ShowroomPlugin::class)->navigation());

    expect($views)->toBe([])
        ->and($urls)->not->toBeEmpty()
        ->and(array_filter($urls, fn (string $url): bool => str_contains($url, 'history')))->toBe([]);
});
