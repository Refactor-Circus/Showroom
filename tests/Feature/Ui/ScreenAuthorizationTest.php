<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Atrium\Domains\Navigation\Services\NavigationRegistry;
use RefactorCircus\Atrium\Domains\Widgets\Services\WidgetRegistry;
use RefactorCircus\Showroom\Atrium\ShowroomPlugin;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeGroupModel;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Category\Models\CategoryModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Family\Models\FamilyModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;
use Workbench\App\Models\User;

/*
 * Every Atrium screen asks Showroom's policies exactly as the JSON API does:
 * these tests grant abilities one at a time and check that the matching
 * navigation and controls appear, and that everything else is hidden and
 * refused.
 */

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');

    // Abilities are granted per user as "ability Model", such as
    // "update Product", and nothing else is allowed.
    Gate::before(function (mixed $user, string $ability, array $arguments): ?bool {
        $subject = $arguments[0] ?? null;
        $class = $subject instanceof Model ? $subject::class : $subject;

        if (! is_string($class) || ! str_starts_with($class, 'RefactorCircus\\Showroom\\Domains\\') || ! str_contains($class, '\\Models\\')) {
            return null;
        }

        // Grants name the entity, which is the model's name less its suffix.
        $entity = Str::replaceEnd('Model', '', class_basename($class));

        return $user instanceof User && in_array($ability.' '.$entity, screenGrants()[$user->getKey()] ?? [], true);
    });
});

/**
 * The abilities each test user holds, by user key.
 *
 * @param  array<int|string, array<int, string>>|null  $set
 * @return array<int|string, array<int, string>>
 */
function screenGrants(?array $set = null): array
{
    static $grants = [];

    if ($set !== null) {
        $grants = $set;
    }

    return $grants;
}

/**
 * A signed-in user holding exactly these abilities.
 *
 * @param  array<int, string>  $grants
 */
function holding(array $grants = []): User
{
    $user = User::forceCreate(['name' => 'Someone', 'email' => fake()->unique()->safeEmail(), 'password' => 'x']);

    screenGrants([...screenGrants(), $user->getKey() => $grants]);

    return $user;
}

function control(string $id): string
{
    return 'data-testid="'.$id.'"';
}

/**
 * The route key of a record named in a dataset. Product and owner screens
 * bind by `id`, so their identifier or code is swapped for the record's id;
 * every other screen keeps its code.
 */
function screenRecordKey(string $route, string $key): string
{
    return match (true) {
        str_starts_with($route, 'atrium.showroom.products.') => ProductModel::query()->where('identifier', $key)->firstOrFail()->id,
        str_starts_with($route, 'atrium.showroom.owners.') => OwnerModel::query()->where('code', $key)->firstOrFail()->id,
        default => $key,
    };
}

/**
 * A small catalog built through the screens while authorization is off,
 * which is then turned on.
 */
function screenWorld(): void
{
    $test = test();

    Catalog::apparel();

    $test->post(route('atrium.showroom.products.store'), ['identifier' => 'TEE-1', 'family' => 'shirts'])->assertRedirect();
    $test->patch(route('atrium.showroom.products.update', screenRecordKey('atrium.showroom.products.update', 'TEE-1')), ['enabled' => '0'])->assertSessionHasNoErrors();

    $test->post(route('atrium.showroom.product-models.store'), ['code' => 'tee', 'family_variant' => 'shirts_by_size'])->assertRedirect();
    $test->post(route('atrium.showroom.products.store'), [
        'identifier' => 'TEE-S',
        'parent' => 'tee',
        'values' => ['size' => [['locale' => '', 'scope' => '', 'data' => 's']]],
    ])->assertSessionHasNoErrors();
    $test->post(route('atrium.showroom.product-models.store'), ['code' => 'polo', 'family_variant' => 'shirts_by_color_size'])->assertRedirect();

    $test->post(route('atrium.showroom.association-types.store'), ['code' => 'related'])->assertRedirect();
    $test->post(route('atrium.showroom.associations.add'), [
        'source_kind' => 'product', 'source' => 'TEE-1', 'type' => 'related', 'target_kind' => 'products', 'target' => 'TEE-S',
    ])->assertSessionHasNoErrors();

    $test->post(route('atrium.showroom.assets.store'), ['code' => 'hero', 'file' => UploadedFile::fake()->image('hero.jpg')])->assertRedirect();
    $test->post(route('atrium.showroom.assets.attach', 'hero'), ['type' => 'product', 'target' => 'TEE-1', 'role' => 'image'])->assertSessionHasNoErrors();

    $test->post(route('atrium.showroom.categories.store'), ['code' => 'master'])->assertRedirect();
    $test->post(route('atrium.showroom.owner-types.store'), ['code' => 'vendor', 'any_parent' => '1', 'can_be_root' => '1', 'owns_products' => '1'])->assertRedirect();
    $test->post(route('atrium.showroom.owners.store'), ['code' => 'acme', 'type' => 'vendor'])->assertRedirect();
    $test->post(route('atrium.showroom.attribute-groups.store'), ['code' => 'basics'])->assertRedirect();
    $test->post(route('atrium.showroom.families.store'), ['code' => 'pants'])->assertRedirect();

    config()->set('showroom.authorization', true);
}

/**
 * @return array<int, string>
 */
function showroomNavigation(?Authenticatable $user): array
{
    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): ?Authenticatable => $user);

    return array_values(array_map(
        fn (NavItem $item): string => $item->label,
        array_filter(app(NavigationRegistry::class)->items($request), fn (NavItem $item): bool => $item->group === __('showroom::showroom.catalog')),
    ));
}

it('shows every navigation item when authorization is off', function (): void {
    expect(showroomNavigation(null))->toHaveCount(11);
});

it('gives every navigation item an icon', function (): void {
    foreach (app(ShowroomPlugin::class)->navigation() as $item) {
        expect($item->icon)->toContain('<svg');
    }
});

it('shows no navigation to a user without abilities or a guest', function (): void {
    config()->set('showroom.authorization', true);

    expect(showroomNavigation(holding()))->toBe([])
        ->and(showroomNavigation(null))->toBe([]);
});

it('shows each navigation item with the ability its list asks', function (string $grant, array $label): void {
    config()->set('showroom.authorization', true);

    expect(showroomNavigation(holding([$grant])))->toBe($label);
})->with([
    // Listing products is also what an export asks.
    ['viewAny Product', ['Products', 'Import & export']],
    ['viewAny ProductModel', ['Product models']],
    ['viewAny Category', ['Categories']],
    ['viewAny Asset', ['Assets']],
    ['viewAny Owner', ['Owners']],
    ['viewAny Family', ['Families']],
    ['viewAny Attribute', ['Attributes']],
    ['viewAny Channel', ['Channels']],
    ['viewAny AssociationType', ['Association types']],
    ['viewAny AttributeGroup', ['Attribute groups']],
]);

it('shows import and export to those who may import or export', function (): void {
    config()->set('showroom.authorization', true);

    expect(showroomNavigation(holding(['create Product'])))->toBe(['Import & export']);

    $this->actingAs(holding(['create Product']))
        ->get(route('atrium.showroom.transfers.index'))
        ->assertOk()
        ->assertSee(control('import-card'), false)
        ->assertDontSee(control('export-card'), false);

    $this->actingAs(holding(['viewAny Product']))
        ->get(route('atrium.showroom.transfers.index'))
        ->assertOk()
        ->assertSee(control('export-card'), false)
        ->assertDontSee(control('import-card'), false);

    $this->actingAs(holding())->get(route('atrium.showroom.transfers.index'))->assertForbidden();
});

it('offers widgets to those who may list products', function (): void {
    config()->set('showroom.authorization', true);

    $offered = function (?User $user): array {
        $request = Request::create('/atrium');
        $request->setUserResolver(fn (): ?User => $user);

        return array_values(array_filter(
            array_keys(app(WidgetRegistry::class)->available($request)),
            fn (string $key): bool => str_starts_with($key, 'showroom.'),
        ));
    };

    expect($offered(holding()))->toBe([])
        ->and($offered(holding(['viewAny Product'])))->toHaveCount(4);
});

it('searches only the records the user may list', function (): void {
    screenWorld();

    $this->actingAs(holding(['viewAny Category']));

    $source = app(ShowroomPlugin::class)->search();
    $groups = array_unique(array_map(fn ($result): ?string => $result->group, $source?->results('m') ?? []));

    expect($source?->isAuthorized(request()))->toBeTrue()
        ->and(array_values($groups))->toBe([__('showroom::showroom.categories')]);
});

it('refuses each list page without its ability', function (string $route, string $grant): void {
    config()->set('showroom.authorization', true);

    $this->actingAs(holding())->get(route($route))->assertForbidden();
    $this->actingAs(holding([$grant]))->get(route($route))->assertOk();
})->with([
    ['atrium.showroom.products.index', 'viewAny Product'],
    ['atrium.showroom.products.create', 'create Product'],
    ['atrium.showroom.product-models.index', 'viewAny ProductModel'],
    ['atrium.showroom.categories.index', 'viewAny Category'],
    ['atrium.showroom.assets.index', 'viewAny Asset'],
    ['atrium.showroom.owners.index', 'viewAny Owner'],
    ['atrium.showroom.owner-types.index', 'viewAny OwnerType'],
    ['atrium.showroom.families.index', 'viewAny Family'],
    ['atrium.showroom.attributes.index', 'viewAny Attribute'],
    ['atrium.showroom.attributes.create', 'create Attribute'],
    ['atrium.showroom.channels.index', 'viewAny Channel'],
    ['atrium.showroom.association-types.index', 'viewAny AssociationType'],
    ['atrium.showroom.attribute-groups.index', 'viewAny AttributeGroup'],
]);

it('hides list page controls without their abilities', function (string $route, array $grants, string $control): void {
    screenWorld();

    $page = fn (array $held) => $this->actingAs(holding($held))->get(route($route))->assertOk();

    $page([$grants[0]])->assertDontSee(control($control), false);
    $page($grants)->assertSee(control($control), false);
})->with([
    'new product' => ['atrium.showroom.products.index', ['viewAny Product', 'create Product'], 'new-product'],
    'new product model' => ['atrium.showroom.product-models.index', ['viewAny ProductModel', 'create ProductModel'], 'new-product-model-card'],
    'upload asset' => ['atrium.showroom.assets.index', ['viewAny Asset', 'create Asset'], 'upload-asset'],
    'new attribute' => ['atrium.showroom.attributes.index', ['viewAny Attribute', 'create Attribute'], 'new-attribute'],
    'new group' => ['atrium.showroom.attribute-groups.index', ['viewAny AttributeGroup', 'create AttributeGroup'], 'new-group-card'],
    'new tree' => ['atrium.showroom.categories.index', ['viewAny Category', 'create Category'], 'new-tree-card'],
    'new channel' => ['atrium.showroom.channels.index', ['viewAny Channel', 'create Channel'], 'new-channel-card'],
    'locales' => ['atrium.showroom.channels.index', ['viewAny Channel', 'viewAny Locale'], 'locales-card'],
    'new locale' => ['atrium.showroom.channels.index', ['viewAny Channel', 'viewAny Locale', 'create Locale'], 'create-locale'],
    'delete locale' => ['atrium.showroom.channels.index', ['viewAny Channel', 'viewAny Locale', 'delete Locale'], 'delete-locale'],
    'new association type' => ['atrium.showroom.association-types.index', ['viewAny AssociationType', 'create AssociationType'], 'new-association-type-card'],
    'delete association type' => ['atrium.showroom.association-types.index', ['viewAny AssociationType', 'delete AssociationType'], 'delete-association-type'],
    'new family' => ['atrium.showroom.families.index', ['viewAny Family', 'create Family'], 'new-family-card'],
    'new owner' => ['atrium.showroom.owners.index', ['viewAny Owner', 'create Owner'], 'new-owner-card'],
    'owner types' => ['atrium.showroom.owners.index', ['viewAny Owner', 'viewAny OwnerType'], 'owner-types'],
    'new owner type' => ['atrium.showroom.owner-types.index', ['viewAny OwnerType', 'create OwnerType'], 'new-owner-type-card'],
]);

it('shows a product to a viewer without its controls', function (): void {
    screenWorld();

    $this->actingAs(holding(['view Product']))
        ->get(route('atrium.showroom.products.show', screenRecordKey('atrium.showroom.products.show', 'TEE-1')))
        ->assertOk()
        ->assertSee('<fieldset class="flex min-w-0 flex-col gap-5" disabled', false)
        ->assertDontSee(control('save-product'), false)
        ->assertDontSee(control('delete-product'), false)
        ->assertDontSee(control('transition-form'), false)
        ->assertDontSee('data-testid="revert-', false)
        ->assertDontSee(control('upload-and-link'), false)
        ->assertDontSee(control('add-association'), false)
        ->assertDontSee(control('remove-association'), false);
});

it('hides record page controls without their abilities', function (string $route, string $key, array $grants, string $control): void {
    screenWorld();

    $key = screenRecordKey($route, $key);
    $page = fn (array $held) => $this->actingAs(holding($held))->get(route($route, $key))->assertOk();

    $page([$grants[0]])->assertDontSee(control($control), false);
    $page($grants)->assertSee(control($control), false);
})->with([
    'save product' => ['atrium.showroom.products.show', 'TEE-1', ['view Product', 'update Product'], 'save-product'],
    'transition product' => ['atrium.showroom.products.show', 'TEE-1', ['view Product', 'update Product'], 'transition-form'],
    'revert product' => ['atrium.showroom.products.show', 'TEE-1', ['view Product', 'update Product'], 'revert-1'],
    'add association' => ['atrium.showroom.products.show', 'TEE-1', ['view Product', 'update Product'], 'add-association'],
    'remove association' => ['atrium.showroom.products.show', 'TEE-1', ['view Product', 'update Product'], 'remove-association'],
    'delete product' => ['atrium.showroom.products.show', 'TEE-1', ['view Product', 'delete Product'], 'delete-product'],
    'upload to product' => ['atrium.showroom.products.show', 'TEE-1', ['view Product', 'create Asset'], 'upload-and-link'],
    'parent model' => ['atrium.showroom.products.show', 'TEE-S', ['view Product', 'view ProductModel'], 'parent-model'],
    'save model' => ['atrium.showroom.product-models.show', 'tee', ['view ProductModel', 'update ProductModel'], 'save-product-model'],
    'delete model' => ['atrium.showroom.product-models.show', 'tee', ['view ProductModel', 'delete ProductModel'], 'delete-product-model'],
    'add variant' => ['atrium.showroom.product-models.show', 'tee', ['view ProductModel', 'create Product'], 'create-variant'],
    'add sub-model' => ['atrium.showroom.product-models.show', 'polo', ['view ProductModel', 'create ProductModel'], 'create-sub-model'],
    'edit asset' => ['atrium.showroom.assets.show', 'hero', ['view Asset', 'update Asset'], 'asset-details-card'],
    'link asset' => ['atrium.showroom.assets.show', 'hero', ['view Asset', 'update Asset'], 'attach-asset'],
    'unlink asset' => ['atrium.showroom.assets.show', 'hero', ['view Asset', 'update Asset'], 'detach-asset'],
    'delete asset' => ['atrium.showroom.assets.show', 'hero', ['view Asset', 'delete Asset'], 'delete-asset'],
    'save attribute' => ['atrium.showroom.attributes.show', 'color', ['view Attribute', 'update Attribute'], 'save-attribute'],
    'delete attribute' => ['atrium.showroom.attributes.show', 'color', ['view Attribute', 'delete Attribute'], 'delete-attribute'],
    'delete option' => ['atrium.showroom.attributes.show', 'color', ['view Attribute', 'delete AttributeOption'], 'delete-option'],
    'save group' => ['atrium.showroom.attribute-groups.show', 'basics', ['view AttributeGroup', 'update AttributeGroup'], 'group-details-card'],
    'delete group' => ['atrium.showroom.attribute-groups.show', 'basics', ['view AttributeGroup', 'delete AttributeGroup'], 'delete-group'],
    'save category' => ['atrium.showroom.categories.show', 'master', ['view Category', 'update Category'], 'category-details-card'],
    'delete category' => ['atrium.showroom.categories.show', 'master', ['view Category', 'delete Category'], 'delete-category'],
    'add subcategory' => ['atrium.showroom.categories.show', 'master', ['view Category', 'create Category'], 'add-subcategory'],
    'category products' => ['atrium.showroom.categories.show', 'master', ['view Category', 'viewAny Product'], 'category-products'],
    'save channel' => ['atrium.showroom.channels.show', 'ecommerce', ['view Channel', 'update Channel'], 'channel-details-card'],
    'delete channel' => ['atrium.showroom.channels.show', 'ecommerce', ['view Channel', 'delete Channel'], 'delete-channel'],
    'save family' => ['atrium.showroom.families.show', 'shirts', ['view Family', 'update Family'], 'save-family'],
    'delete family' => ['atrium.showroom.families.show', 'shirts', ['view Family', 'delete Family'], 'delete-family'],
    'add family variant' => ['atrium.showroom.families.show', 'shirts', ['view Family', 'create FamilyVariant'], 'create-family-variant'],
    'variant family' => ['atrium.showroom.family-variants.show', 'shirts_by_size', ['view FamilyVariant', 'view Family'], 'variant-family'],
    'delete family variant' => ['atrium.showroom.family-variants.show', 'shirts_by_size', ['view FamilyVariant', 'delete FamilyVariant'], 'delete-family-variant'],
    'save owner' => ['atrium.showroom.owners.show', 'acme', ['view Owner', 'update Owner'], 'owner-details-card'],
    'delete owner' => ['atrium.showroom.owners.show', 'acme', ['view Owner', 'delete Owner'], 'delete-owner'],
    'add child owner' => ['atrium.showroom.owners.show', 'acme', ['view Owner', 'create Owner'], 'add-child-owner'],
    'owner products' => ['atrium.showroom.owners.show', 'acme', ['view Owner', 'viewAny Product'], 'owner-products'],
    'upload to owner' => ['atrium.showroom.owners.show', 'acme', ['view Owner', 'create Asset'], 'upload-and-link'],
    'save owner type' => ['atrium.showroom.owner-types.show', 'vendor', ['view OwnerType', 'update OwnerType'], 'owner-type-details-card'],
    'delete owner type' => ['atrium.showroom.owner-types.show', 'vendor', ['view OwnerType', 'delete OwnerType'], 'delete-owner-type'],
    'type owners' => ['atrium.showroom.owner-types.show', 'vendor', ['view OwnerType', 'viewAny Owner'], 'type-owners'],
]);

it('asks for both abilities the API asks to add an option', function (): void {
    screenWorld();

    $page = fn (array $held) => $this->actingAs(holding($held))->get(route('atrium.showroom.attributes.show', 'color'))->assertOk();

    $page(['view Attribute', 'update Attribute'])->assertDontSee(control('add-option'), false);
    $page(['view Attribute', 'create AttributeOption'])->assertDontSee(control('add-option'), false);
    $page(['view Attribute', 'update Attribute', 'create AttributeOption'])->assertSee(control('add-option'), false);
});

it('refuses each record page without view', function (string $route, string $key): void {
    screenWorld();

    $this->actingAs(holding())->get(route($route, screenRecordKey($route, $key)))->assertForbidden();
})->with([
    ['atrium.showroom.products.show', 'TEE-1'],
    ['atrium.showroom.product-models.show', 'tee'],
    ['atrium.showroom.assets.show', 'hero'],
    ['atrium.showroom.attributes.show', 'color'],
    ['atrium.showroom.attribute-groups.show', 'basics'],
    ['atrium.showroom.categories.show', 'master'],
    ['atrium.showroom.channels.show', 'ecommerce'],
    ['atrium.showroom.families.show', 'shirts'],
    ['atrium.showroom.family-variants.show', 'shirts_by_size'],
    ['atrium.showroom.owners.show', 'acme'],
    ['atrium.showroom.owner-types.show', 'vendor'],
]);

it('refuses each action without its ability, and allows it with', function (string $method, string $route, array $parameters, array $data, string $grant): void {
    screenWorld();

    if ($parameters !== []) {
        $parameters[0] = screenRecordKey($route, $parameters[0]);
    }

    $this->actingAs(holding())->call($method, route($route, $parameters), $data)->assertForbidden();

    // Allowed: the action runs (a redirect), rather than being refused.
    $this->actingAs(holding([$grant]))->call($method, route($route, $parameters), $data)->assertRedirect();
})->with([
    'create product' => ['POST', 'atrium.showroom.products.store', [], ['identifier' => 'NEW-1'], 'create Product'],
    'update product' => ['PATCH', 'atrium.showroom.products.update', ['TEE-1'], ['enabled' => '1'], 'update Product'],
    'transition product' => ['POST', 'atrium.showroom.products.transition', ['TEE-1'], ['transition' => 'submit'], 'update Product'],
    'revert product' => ['POST', 'atrium.showroom.products.revert', ['TEE-1'], ['version' => 1], 'update Product'],
    'delete product' => ['DELETE', 'atrium.showroom.products.destroy', ['TEE-1'], [], 'delete Product'],
    'create model' => ['POST', 'atrium.showroom.product-models.store', [], ['code' => 'new', 'family_variant' => 'shirts_by_size'], 'create ProductModel'],
    'update model' => ['PATCH', 'atrium.showroom.product-models.update', ['tee'], [], 'update ProductModel'],
    'delete model' => ['DELETE', 'atrium.showroom.product-models.destroy', ['polo'], [], 'delete ProductModel'],
    'add association' => ['POST', 'atrium.showroom.associations.add', [], ['source_kind' => 'product', 'source' => 'TEE-S', 'type' => 'related', 'target_kind' => 'products', 'target' => 'TEE-1'], 'update Product'],
    'remove association' => ['DELETE', 'atrium.showroom.associations.remove', [], ['source_kind' => 'product', 'source' => 'TEE-1', 'type' => 'related', 'target_kind' => 'products', 'target' => 'TEE-S'], 'update Product'],
    'create association type' => ['POST', 'atrium.showroom.association-types.store', [], ['code' => 'upsell'], 'create AssociationType'],
    'delete association type' => ['DELETE', 'atrium.showroom.association-types.destroy', ['related'], [], 'delete AssociationType'],
    'create channel' => ['POST', 'atrium.showroom.channels.store', [], ['code' => 'b2b'], 'create Channel'],
    'update channel' => ['PATCH', 'atrium.showroom.channels.update', ['print'], [], 'update Channel'],
    'delete channel' => ['DELETE', 'atrium.showroom.channels.destroy', ['print'], [], 'delete Channel'],
    'create locale' => ['POST', 'atrium.showroom.locales.store', [], ['code' => 'es'], 'create Locale'],
    'delete locale' => ['DELETE', 'atrium.showroom.locales.destroy', ['de'], [], 'delete Locale'],
    'create asset' => ['POST', 'atrium.showroom.assets.store', [], ['file' => UploadedFile::fake()->image('a.jpg')], 'create Asset'],
    'update asset' => ['PATCH', 'atrium.showroom.assets.update', ['hero'], [], 'update Asset'],
    'delete asset' => ['DELETE', 'atrium.showroom.assets.destroy', ['hero'], [], 'delete Asset'],
    'attach asset' => ['POST', 'atrium.showroom.assets.attach', ['hero'], ['type' => 'product', 'target' => 'TEE-S'], 'update Asset'],
    'detach asset' => ['DELETE', 'atrium.showroom.assets.detach', ['hero'], ['type' => 'product', 'target' => 'TEE-1', 'role' => 'image'], 'update Asset'],
    'create category' => ['POST', 'atrium.showroom.categories.store', [], ['code' => 'outlet'], 'create Category'],
    'update category' => ['PATCH', 'atrium.showroom.categories.update', ['master'], [], 'update Category'],
    'delete category' => ['DELETE', 'atrium.showroom.categories.destroy', ['master'], [], 'delete Category'],
    'create owner type' => ['POST', 'atrium.showroom.owner-types.store', [], ['code' => 'brand', 'any_parent' => '1'], 'create OwnerType'],
    'update owner type' => ['PATCH', 'atrium.showroom.owner-types.update', ['vendor'], ['any_parent' => '1'], 'update OwnerType'],
    'delete owner type' => ['DELETE', 'atrium.showroom.owner-types.destroy', ['vendor'], [], 'delete OwnerType'],
    'create owner' => ['POST', 'atrium.showroom.owners.store', [], ['code' => 'initech', 'type' => 'vendor'], 'create Owner'],
    'update owner' => ['PATCH', 'atrium.showroom.owners.update', ['acme'], [], 'update Owner'],
    'delete owner' => ['DELETE', 'atrium.showroom.owners.destroy', ['acme'], [], 'delete Owner'],
    'create family' => ['POST', 'atrium.showroom.families.store', [], ['code' => 'pants'], 'create Family'],
    'update family' => ['PATCH', 'atrium.showroom.families.update', ['shirts'], [], 'update Family'],
    'delete family' => ['DELETE', 'atrium.showroom.families.destroy', ['pants'], [], 'delete Family'],
    'create family variant' => ['POST', 'atrium.showroom.family-variants.store', [], ['code' => 'by_color', 'family' => 'shirts', 'levels' => [['axes' => ['color']]]], 'create FamilyVariant'],
    'delete family variant' => ['DELETE', 'atrium.showroom.family-variants.destroy', ['shirts_by_color_size'], [], 'delete FamilyVariant'],
    'create group' => ['POST', 'atrium.showroom.attribute-groups.store', [], ['code' => 'extras'], 'create AttributeGroup'],
    'update group' => ['PATCH', 'atrium.showroom.attribute-groups.update', ['basics'], [], 'update AttributeGroup'],
    'delete group' => ['DELETE', 'atrium.showroom.attribute-groups.destroy', ['basics'], [], 'delete AttributeGroup'],
    'create attribute' => ['POST', 'atrium.showroom.attributes.store', [], ['code' => 'material', 'type' => 'text'], 'create Attribute'],
    'update attribute' => ['PATCH', 'atrium.showroom.attributes.update', ['color'], [], 'update Attribute'],
    'delete attribute' => ['DELETE', 'atrium.showroom.attributes.destroy', ['released'], [], 'delete Attribute'],
    'delete option' => ['DELETE', 'atrium.showroom.attributes.options.destroy', ['color', 'green'], [], 'delete AttributeOption'],
    'import' => ['POST', 'atrium.showroom.transfers.import', [], [], 'create Product'],
    'export' => ['POST', 'atrium.showroom.transfers.export', [], [], 'viewAny Product'],
]);

it('asks for update on the attribute and create on options to add one', function (): void {
    screenWorld();

    $add = fn (array $held) => $this->actingAs(holding($held))->post(route('atrium.showroom.attributes.options.store', 'color'), ['code' => 'black']);

    $add(['update Attribute'])->assertForbidden();
    $add(['create AttributeOption'])->assertForbidden();
    $add(['update Attribute', 'create AttributeOption'])->assertRedirect();
});

it('rolls an upload back when the user may create an asset but not link it', function (): void {
    screenWorld();

    $upload = fn (array $held) => $this->actingAs(holding($held))->post(route('atrium.showroom.assets.upload'), [
        'type' => 'product', 'target' => 'TEE-1', 'role' => 'image', 'file' => UploadedFile::fake()->image('front.jpg'),
    ]);

    $before = AssetModel::query()->count();

    $upload([])->assertForbidden();
    $upload(['create Asset'])->assertForbidden();

    expect(AssetModel::query()->count())->toBe($before);

    $upload(['create Asset', 'update Asset'])->assertRedirect();

    expect(AssetModel::query()->count())->toBe($before + 1);
});

it('leaves records untouched when an action is refused', function (): void {
    screenWorld();

    $this->actingAs(holding(['view Product']))->delete(route('atrium.showroom.products.destroy', screenRecordKey('atrium.showroom.products.destroy', 'TEE-1')))->assertForbidden();
    $this->actingAs(holding(['view Category']))->delete(route('atrium.showroom.categories.destroy', 'master'))->assertForbidden();

    expect(ProductModel::query()->where('identifier', 'TEE-1')->exists())->toBeTrue()
        ->and(CategoryModel::query()->where('code', 'master')->exists())->toBeTrue()
        ->and(ProductModelModel::query()->count())->toBe(2)
        ->and(OwnerModel::query()->count())->toBe(1)
        ->and(OwnerTypeModel::query()->count())->toBe(1)
        ->and(FamilyModel::query()->count())->toBe(2)
        ->and(ChannelModel::query()->count())->toBe(2)
        ->and(LocaleModel::query()->count())->toBe(3)
        ->and(AssociationTypeModel::query()->count())->toBe(1)
        ->and(AttributeGroupModel::query()->count())->toBe(1)
        ->and(AttributeModel::query()->where('code', 'color')->exists())->toBeTrue();
});
